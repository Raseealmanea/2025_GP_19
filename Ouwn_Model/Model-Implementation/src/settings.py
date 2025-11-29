from omegaconf import OmegaConf

PAD_TOKEN = "<PAD>"
UNKNOWN_TOKEN = "<UNK>"

ID_COLUMN = "_id"
TEXT_COLUMN = "ner_clean_text"
TARGET_COLUMN = "target"
SUBJECT_ID_COLUMN = "subject_id"

DATA_DIRECTORY_MIMICIV_ICD10 = OmegaConf.load("configs/data/mimiciv_icd10.yaml").dir

PROJECT = "mimic-icd10-plmicd" # this variable is used for genersating plots and tables from wandb
EXPERIMENT_DIR = "files/"  # Path to the experiment directory. Example: ~/experiments
PALETTE = {
    "PLM-ICD": "#E69F00"
}
HUE_ORDER = ["PLM-ICD"]
MODEL_NAMES = {"PLMICD": "PLM-ICD"}
