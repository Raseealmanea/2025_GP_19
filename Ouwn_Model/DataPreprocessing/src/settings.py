from omegaconf import OmegaConf

PAD_TOKEN = "<PAD>"
UNKNOWN_TOKEN = "<UNK>"

ID_COLUMN = "_id"
TEXT_COLUMN = "text"
TARGET_COLUMN = "target"
SUBJECT_ID_COLUMN = "subject_id"


DOWNLOAD_DIRECTORY_MIMICIV = "/Users/layal/Desktop/mimiciv"  # Path to the MIMIC-IV data. Example: ~/physionet.org/files/mimiciv/2.2
DOWNLOAD_DIRECTORY_MIMICIV_NOTE = "/Users/layal/Desktop/mimiciv-note"  # Path to the MIMIC-IV-Note data. Example: ~/physionet.org/files/mimic-iv-note/2.2



DATA_DIRECTORY_MIMICIV_ICD9 = OmegaConf.load("configs/data/mimiciv_icd9.yaml").dir
DATA_DIRECTORY_MIMICIV_ICD10 = OmegaConf.load("configs/data/mimiciv_icd10.yaml").dir

PROJECT = "<preprocessing>" # this variable is used for genersating plots and tables from wandb
EXPERIMENT_DIR = "/Users/layal/Desktop/exp"  # Path to the experiment directory. Example: ~/experiments
PALETTE = {
    "PLM-ICD": "#E69F00",
    "LAAT": "#009E73",
    "MultiResCNN": "#D55E00",
    "CAML": "#56B4E9",
    "CNN": "#CC79A7",
    "Bi-GRU": "#F5C710",
}
HUE_ORDER = ["PLM-ICD", "LAAT", "MultiResCNN", "CAML", "Bi-GRU", "CNN"]
MODEL_NAMES = {"PLMICD": "PLM-ICD", "VanillaConv": "CNN", "VanillaRNN": "Bi-GRU"}
