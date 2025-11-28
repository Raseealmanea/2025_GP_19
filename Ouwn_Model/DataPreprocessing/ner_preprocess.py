import torch
import pandas as pd
from tqdm.auto import tqdm
from transformers import AutoTokenizer, AutoModelForTokenClassification, pipeline

# ---------- CONFIG: change if needed ---------- #
MODEL_NAME = "blaze999/Medical-NER"
DATA_DIR = "/content/drive/MyDrive/medcode_data/mimiciv_icd10"
FILE_NAME = "mimiciv_icd10.feather"         # your existing file
TEXT_COLUMN = "text"                         # whatever your current text column is
NEW_COLUMN = "ner_clean_text"               # will be added
# --------------------------------------------- #

INPUT_PATH = f"{DATA_DIR}/{FILE_NAME}"


def load_ner_pipeline():
    device = 0 if torch.cuda.is_available() else -1
    print(f"Loading NER model on device={device} ...")
    tokenizer = AutoTokenizer.from_pretrained(MODEL_NAME)
    model = AutoModelForTokenClassification.from_pretrained(MODEL_NAME)

    return pipeline(
        "token-classification",
        model=model,
        tokenizer=tokenizer,
        aggregation_strategy="simple",
        device=device,
    )


def clean_word(word: str) -> str:
    for p in ("##", "▁", "Ġ"):
        if word.startswith(p):
            word = word[len(p):]
    return word


def extract_entities(ner_pipe, text: str) -> str:
    if not isinstance(text, str) or not text.strip():
        return ""
    try:
        ents = ner_pipe(text)
    except Exception as e:
        print("NER error:", e)
        return ""
    words = [clean_word(e.get("word", "")) for e in ents if clean_word(e.get("word", ""))]
    return " ".join(words)


def main():
    print(f"Loading data from {INPUT_PATH}")
    df = pd.read_feather(INPUT_PATH)

    if TEXT_COLUMN not in df.columns:
        raise ValueError(f"{TEXT_COLUMN} not found. Available: {df.columns}")

    ner_pipe = load_ner_pipeline()

    clean_texts = []
    print("Running Medical-NER over notes...")
    for txt in tqdm(df[TEXT_COLUMN].tolist()):
        clean_texts.append(extract_entities(ner_pipe, txt))

    df[NEW_COLUMN] = clean_texts

    # overwrite the same file (just adds a column)
    df.to_feather(INPUT_PATH)
    print(f"Saved back to {INPUT_PATH} with new column '{NEW_COLUMN}'")
    print(df[[TEXT_COLUMN, NEW_COLUMN]].head())


if __name__ == "__main__":
    main()
