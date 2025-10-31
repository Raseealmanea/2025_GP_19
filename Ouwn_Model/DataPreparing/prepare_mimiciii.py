#!/usr/bin/env python
# coding: utf-8

# In[38]:


import os
import pandas as pd
from collections import Counter
import re
import logging
import unidecode 

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
    datefmt="%Y-%m-%d %H:%M:%S"
)

# Define paths

DATA_DIRECTORY_MIMICIII = r"C:\Users\Dalya\Downloads\mimic-iii-clinical-database-1.4\MIMICIII"
OUTPUT_PATH = os.path.join(DATA_DIRECTORY_MIMICIII, "MIMICIII_ready_for_NLP_notes.csv")

# Ensure output directory exists
os.makedirs(os.path.dirname(OUTPUT_PATH), exist_ok=True)
logging.info(f"Output directory ready: {os.path.dirname(OUTPUT_PATH)}")

NOTEEVENTS_FILE = os.path.join(DATA_DIRECTORY_MIMICIII, "NOTEEVENTS.csv", "NOTEEVENTS.csv")
DIAGNOSES_FILE = os.path.join(DATA_DIRECTORY_MIMICIII, "DIAGNOSES_ICD.csv.gz")
PROCEDURES_FILE = os.path.join(DATA_DIRECTORY_MIMICIII, "PROCEDURES_ICD.csv.gz")


# In[39]:


logging.info("Loading NOTEEVENTS...")
notes = pd.read_csv(NOTEEVENTS_FILE, usecols=['SUBJECT_ID', 'HADM_ID', 'CATEGORY', 'TEXT'])
notes = notes[notes['CATEGORY'] == 'Discharge summary']
logging.info(f"Loaded {len(notes)} discharge summaries")

notes = notes.groupby('HADM_ID').agg({
    'SUBJECT_ID': 'first',       # keep one SUBJECT_ID per admission
    'TEXT': ' '.join             # combine all notes into one string
}).reset_index()
logging.info(f"Merged multiple notes per HADM_ID, now {len(notes)} rows")

logging.info("Loading diagnoses and procedures...")
diagnoses = pd.read_csv(DIAGNOSES_FILE, compression='gzip', dtype={'ICD9_CODE': str})
procedures = pd.read_csv(PROCEDURES_FILE, compression='gzip', dtype={'ICD9_CODE': str})


# In[42]:


diag_grouped = diagnoses.groupby('HADM_ID')['ICD9_CODE'].apply(list).reset_index()
proc_grouped = procedures.groupby('HADM_ID')['ICD9_CODE'].apply(list).reset_index()

# Merge diagnoses and procedures
icd_all = pd.merge(diag_grouped, proc_grouped, on='HADM_ID', how='outer')

# Replace missing lists with empty lists
def replace_nans_with_empty_lists(df, exclude_cols=None):
    exclude_cols = exclude_cols or []
    for col in df.columns:
        if col not in exclude_cols:
            df[col] = df[col].apply(
                lambda x: [] if (not isinstance(x, list) and pd.isna(x)) else x
            )
    return df


icd_all = replace_nans_with_empty_lists(icd_all, exclude_cols=['HADM_ID'])

# Rename columns
icd_all.rename(columns={'ICD9_CODE_x': 'icd9_diag', 'ICD9_CODE_y': 'icd9_proc'}, inplace=True)

# Optional: ICD-9 formatting function
def reformat_icd9(code, is_diag=True):
    """Fix ICD-9 codes to standard format"""
    code = str(code).zfill(3)  # pad with zeros
    if is_diag and '.' not in code and len(code) > 3:
        code = code[:3] + '.' + code[3:]
    return code

icd_all['icd9_diag'] = icd_all['icd9_diag'].apply(lambda codes: [reformat_icd9(c, is_diag=True) for c in codes])
icd_all['icd9_proc'] = icd_all['icd9_proc'].apply(lambda codes: [reformat_icd9(c, is_diag=False) for c in codes])

# Combine diagnosis and procedure codes into target column
icd_all['target'] = icd_all['icd9_diag'] + icd_all['icd9_proc']
logging.info(f"ICD codes grouped and formatted for {len(icd_all)} admissions")


# In[43]:


data = pd.merge(notes, icd_all, on='HADM_ID', how='inner')
print(f" Merged dataset shape: {data.shape}")


# In[44]:


MIN_TARGET_COUNT = 10

all_codes = [code for codes in data['target'] for code in codes]
code_counts = Counter(all_codes)
codes_to_keep = {c for c, count in code_counts.items() if count >= MIN_TARGET_COUNT}

def filter_codes(codes):
    return [c for c in codes if c in codes_to_keep]
data['target'] = data['target'].apply(lambda codes: [c for c in codes if c in codes_to_keep])
data = data[data['target'].apply(len) > 0]

logging.info(f"Filtered to {len(data)} notes after rare-code removal")


# In[45]:


class TextPreprocessor:
    def __init__(self,
                 lower=True,
                 remove_special_characters_mullenbach=True,
                 remove_special_characters=False,
                 remove_digits=True,
                 remove_accents=False,
                 remove_brackets=False,
                 convert_danish_characters=False):

        self.lower = lower
        self.remove_special_characters_mullenbach = remove_special_characters_mullenbach
        self.remove_special_characters = remove_special_characters
        self.remove_digits = remove_digits
        self.remove_accents = remove_accents
        self.remove_brackets = remove_brackets
        self.convert_danish_characters = convert_danish_characters

    def clean(self, text: str) -> str:

        if not isinstance(text, str):
            text = str(text)

        if self.lower:
            text = text.lower()

        if self.remove_special_characters_mullenbach:
            # Mullenbach-style special character removal (keep spaces and letters)
            text = re.sub(r'[^a-z\s]', ' ', text)

        if self.remove_special_characters:
            # Additional removal of any other unwanted special characters
            text = re.sub(r'[^\w\s]', ' ', text)

        if self.remove_digits:
            text = re.sub(r'\d+', ' ', text)

        if self.remove_accents:
            text = unidecode.unidecode(text)

        if self.remove_brackets:
            text = re.sub(r'[\[\]\(\)\{\}]', ' ', text)

        if self.convert_danish_characters:
            # Example conversions for Danish characters
            text = text.replace('æ', 'ae').replace('ø', 'o').replace('å', 'aa')

        # Collapse multiple spaces
        text = re.sub(r'\s+', ' ', text).strip()
        return text

preprocessor = TextPreprocessor(
    lower=True,
    remove_special_characters_mullenbach=True,
    remove_special_characters=False,
    remove_digits=True,
    remove_accents=True,          
    remove_brackets=True,         
    convert_danish_characters=True
)
data['TEXT'] = data['TEXT'].astype(str).apply(preprocessor.clean)
logging.info("Text cleaned (lowercase, special chars removed)")


# In[47]:


data.to_csv(OUTPUT_PATH, index=False)
logging.info(f"Saved cleaned dataset to CSV at: {OUTPUT_PATH}")


# In[ ]:




