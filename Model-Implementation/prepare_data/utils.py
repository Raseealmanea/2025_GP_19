import logging
from collections import Counter
from functools import partial
from pathlib import Path
from typing import Optional

import pandas as pd
import wget

from src.settings import ID_COLUMN, SUBJECT_ID_COLUMN, TARGET_COLUMN, TEXT_COLUMN


def make_version_dir(output_dir: Path) -> Path:
    """Creates a new directory for the current version of the dataset."""
    if not output_dir.is_dir():
        output_dir = output_dir / "1"
        logging.info(f"Creating directory {output_dir}")
        output_dir.mkdir(parents=True)
    else:
        logging.info(f"Directory {output_dir} already exists")
        latest_version = max(output_dir.iterdir(), key=lambda x: int(x.name))
        new_version = str(int(latest_version.name) + 1)
        logging.info(
            f"Latest version is {latest_version.name}, bumping to {new_version}"
        )
        output_dir = output_dir / new_version
        logging.info(f"Creating directory {output_dir}")
        output_dir.mkdir()
    return output_dir


def get_mimiciii_notes(download_dir: Path) -> pd.DataFrame:
    """Reads the notes from the mimiciii dataset into a dataframe."""
    return load_gz_file_into_df(download_dir / "NOTEEVENTS.feather")


def load_gz_file_into_df(path: Path, dtype: Optional[dict] = None):
    """Reads the notes from a path into a dataframe. Saves the file as a feather file."""
    download_dir = path.parents[0]
    stemmed_filename = path.name.split(".")[0]
    feather_path = download_dir / f"{stemmed_filename}.feather"

    if feather_path.is_file():
        logging.info(
            f"{stemmed_filename}.feather already exists, loading data from {stemmed_filename}.feather into pandas."
        )
        return pd.read_feather(feather_path)

    csv_path = download_dir / f"{stemmed_filename}.csv.gz"
    logging.info(f"Loading data from {csv_path} into pandas. This may take a while...")
    df = pd.read_csv(csv_path, compression="gzip", dtype=dtype)
    df.to_feather(feather_path)
    return df


def download_mullenbach_icd9_description() -> pd.DataFrame:
    """Download the icd9 description file from the Mullenbach GitHub repo."""
    logging.info("Downloading ICD9 description file...")
    url = "https://raw.githubusercontent.com/jamesmullenbach/caml-mimic/master/mimicdata/ICD9_descriptions"
    df = pd.read_csv(url, sep="\t", header=None)
    df.columns = ["icd9_code", "icd9_description"]
    return df


def get_icd9_descriptions(download_dir: Path) -> pd.DataFrame:
    """Gets the ICD9 descriptions."""
    icd9_proc_desc = pd.read_csv(
        download_dir / "D_ICD_PROCEDURES.csv.gz",
        compression="gzip",
        dtype={"ICD9_CODE": str},
    )
    icd9_proc_desc = clean_icd9_desc_df(icd9_proc_desc, is_diag=False)

    icd9_diag_desc = pd.read_csv(
        download_dir / "D_ICD_DIAGNOSES.csv.gz",
        compression="gzip",
        dtype={"ICD9_CODE": str},
    )
    icd9_diag_desc = clean_icd9_desc_df(icd9_diag_desc, is_diag=True)

    icd9_mullenbach_desc = download_mullenbach_icd9_description()
    icd9_desc = pd.concat([icd9_proc_desc, icd9_diag_desc, icd9_mullenbach_desc])
    return icd9_desc.drop_duplicates(subset=["icd9_code"])


def clean_icd9_desc_df(icd_desc: pd.DataFrame, is_diag: bool) -> pd.DataFrame:
    """Cleans ICD9 description dataframe."""
    icd_desc = icd_desc.rename(
        columns={"ICD9_CODE": "icd9_code", "LONG_TITLE": "icd9_description"}
    )
    icd_desc["icd9_code"] = icd_desc["icd9_code"].astype(str)
    icd_desc["icd9_code"] = icd_desc["icd9_code"].apply(
        lambda code: reformat_icd9(code, is_diag)
    )
    return icd_desc[["icd9_code", "icd9_description"]]


def download_mullenbach_splits(splits: list[str], download_directory: Path) -> None:
    """Downloads the Mullenbach splits."""
    for split in splits:
        url = f"https://raw.githubusercontent.com/jamesmullenbach/caml-mimic/master/mimicdata/mimic3/{split}_hadm_ids.csv"
        logging.info(f"\nDownloading - {split}:")
        wget.download(url, str(download_directory))


def get_mullenbach_splits(
    splits: list[str], download_directory: Path
) -> dict[str, pd.DataFrame]:
    """Loads the downloaded Mullenbach splits."""
    splits_dict = {}
    for split in splits:
        logging.info(f"\nLoading - {split}:")
        df = pd.read_csv(download_directory / f"{split}_hadm_ids.csv", header=None)
        df.columns = [ID_COLUMN]
        splits_dict[split] = df
    return splits_dict


def filter_mullenbach_splits(
    splits: dict[str, pd.DataFrame], dataset: pd.DataFrame
) -> dict[str, pd.DataFrame]:
    """Filters Mullenbach splits."""
    filtered = {}
    for split, df in splits.items():
        logging.info(f"\nFiltering - {split}:")
        len_before = len(df)
        filtered_df = df[df[ID_COLUMN].isin(dataset[ID_COLUMN])]
        logging.info(f"\t{len_before} -> {len(filtered_df)}")
        filtered[split] = filtered_df.reset_index(drop=True)
    return filtered


def save_mullenbach_splits(
    splits: dict[str, pd.DataFrame],
    output_directory_50: Path,
    output_directory_full: Path,
) -> None:
    """Saves filtered splits."""
    split_50, split_full = [], []
    for split, df in splits.items():
        df["split"] = split.replace("dev", "val").split("_")[0]
        (split_50 if "50" in split else split_full).append(df)
    pd.concat(split_50).reset_index(drop=True).to_feather(
        output_directory_50 / "mimiciii_50_splits.feather"
    )
    pd.concat(split_full).reset_index(drop=True).to_feather(
        output_directory_full / "mimiciii_full_splits.feather"
    )


def merge_code_dataframes(code_dfs: list[pd.DataFrame]) -> pd.DataFrame:
    """Merges code dataframes."""
    merged = code_dfs[0]
    for code_df in code_dfs[1:]:
        merged = merged.merge(code_df, how="outer", on=[SUBJECT_ID_COLUMN, ID_COLUMN])
    return merged


def replace_nans_with_empty_lists(
    df: pd.DataFrame, columns: list[str] = ["icd9_diag", "icd9_proc"]
) -> pd.DataFrame:
    """Replaces NaNs with empty lists."""
    for column in columns:
        df[column] = df[column].apply(lambda x: x if isinstance(x, list) else [])
    return df


def reformat_icd(code: str, version: int, is_diag: bool) -> str:
    if version == 9:
        return reformat_icd9(code, is_diag)
    elif version == 10:
        return reformat_icd10(code, is_diag)
    else:
        raise ValueError("version must be 9 or 10")


def reformat_icd10(code: str, is_diag: bool) -> str:
    code = "".join(code.split("."))
    if not is_diag:
        return code
    return code[:3] + "." + code[3:]


def reformat_icd9(code: str, is_diag: bool) -> str:
    code = "".join(code.split("."))
    if is_diag:
        if code.startswith("E"):
            return code[:4] + "." + code[4:] if len(code) > 4 else code
        else:
            return code[:3] + "." + code[3:] if len(code) > 3 else code
    else:
        return code[:2] + "." + code[2:] if len(code) > 2 else code


def reformat_code_dataframe(row: pd.DataFrame, col: str) -> pd.Series:
    return pd.Series({col: row[col].sort_values().tolist()})


def merge_report_addendum_helper_function(row: pd.DataFrame) -> pd.Series:
    if len(row) == 1:
        return pd.Series({"DESCRIPTION": row.iloc[0].DESCRIPTION, TEXT_COLUMN: row.iloc[0][TEXT_COLUMN]})
    else:
        return pd.Series({
            "DESCRIPTION": "+".join(row.DESCRIPTION),
            TEXT_COLUMN: " ".join(row[TEXT_COLUMN]),
        })


def format_code_dataframe(df: pd.DataFrame, col_in: str, col_out: str) -> pd.DataFrame:
    df = df.rename(columns={"HADM_ID": ID_COLUMN, "SUBJECT_ID": SUBJECT_ID_COLUMN, "TEXT": TEXT_COLUMN})
    df = df.sort_values([SUBJECT_ID_COLUMN, ID_COLUMN])
    df[col_in] = df[col_in].astype(str).str.strip()
    df = df[[SUBJECT_ID_COLUMN, ID_COLUMN, col_in]].rename({col_in: col_out}, axis=1)
    df = df[df[col_out] != "nan"]
    return (
        df.groupby([SUBJECT_ID_COLUMN, ID_COLUMN])
        .apply(partial(reformat_code_dataframe, col=col_out))
        .reset_index()
    )


def merge_reports_addendum(mimic_notes: pd.DataFrame) -> pd.DataFrame:
    discharge_summaries = mimic_notes[mimic_notes["CATEGORY"] == "Discharge summary"]
    discharge_summaries[ID_COLUMN] = discharge_summaries[ID_COLUMN].astype(int)
    return (
        discharge_summaries.groupby([SUBJECT_ID_COLUMN, ID_COLUMN])
        .apply(merge_report_addendum_helper_function)
        .reset_index()
    )


def top_k_codes(df: pd.DataFrame, column_names: list[str], k: int) -> set[str]:
    counter = Counter()
    for col in column_names:
        list(map(counter.update, df[col]))
    return set(x[0] for x in counter.most_common(k))


def filter_codes(df: pd.DataFrame, column_names: list[str], codes_to_keep: set[str]) -> pd.DataFrame:
    df = df.copy()
    for col in column_names:
        df[col] = df[col].apply(lambda codes: [x for x in codes if x in codes_to_keep])
    return df


def remove_duplicated_codes(df: pd.DataFrame, column_names: list[str]) -> pd.DataFrame:
    df = df.copy()
    for col in column_names:
        df[col] = df[col].apply(lambda codes: list(set(codes)))
    return df


class TextPreprocessor:
    def __init__(
        self,
        lower: bool = True,
        remove_special_characters_mullenbach: bool = True,
        remove_special_characters: bool = False,
        remove_digits: bool = True,
        remove_accents: bool = False,
        remove_brackets: bool = False,
        convert_danish_characters: bool = False,
    ):
        self.lower = lower
        self.remove_special_characters_mullenbach = remove_special_characters_mullenbach
        self.remove_digits = remove_digits
        self.remove_accents = remove_accents
        self.remove_special_characters = remove_special_characters
        self.remove_brackets = remove_brackets
        self.convert_danish_characters = convert_danish_characters

    def __call__(self, df: pd.DataFrame) -> pd.DataFrame:
        if self.lower:
            df[TEXT_COLUMN] = df[TEXT_COLUMN].str.lower()
        if self.convert_danish_characters:
            df[TEXT_COLUMN] = df[TEXT_COLUMN].str.replace("å", "aa", regex=True)
            df[TEXT_COLUMN] = df[TEXT_COLUMN].str.replace("æ", "ae", regex=True)
            df[TEXT_COLUMN] = df[TEXT_COLUMN].str.replace("ø", "oe", regex=True)
        if self.remove_accents:
            df[TEXT_COLUMN] = df[TEXT_COLUMN].replace(
                {"é|è|ê": "e", "á|à|â": "a", "ô|ó|ò": "o"}, regex=True
            )
        if self.remove_brackets:
            df[TEXT_COLUMN] = df[TEXT_COLUMN].str.replace(r"\[[^]]*\]", "", regex=True)
        if self.remove_special_characters:
            df[TEXT_COLUMN] = df[TEXT_COLUMN].str.replace("\n|/|-", " ", regex=True)
            df[TEXT_COLUMN] = df[TEXT_COLUMN].str.replace("[^a-zA-Z0-9 ]", "", regex=True)
        if self.remove_special_characters_mullenbach:
            df[TEXT_COLUMN] = df[TEXT_COLUMN].str.replace("[^A-Za-z0-9]+", " ", regex=True)
        if self.remove_digits:
            df[TEXT_COLUMN] = df[TEXT_COLUMN].str.replace(r"(\s\d+)+\s", " ", regex=True)
        df[TEXT_COLUMN] = df[TEXT_COLUMN].str.replace(r"\s+", " ", regex=True).str.strip()
        return df


def preprocess_documents(df: pd.DataFrame, preprocessor: TextPreprocessor) -> pd.DataFrame:
    df = preprocessor(df)
    df["num_words"] = df[TEXT_COLUMN].str.count(" ") + 1
    df["num_targets"] = df[TARGET_COLUMN].apply(len)
    return df
