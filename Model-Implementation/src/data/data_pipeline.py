
from collections import defaultdict
from pathlib import Path
import numpy as np
import pandas as pd
import pyarrow as pa
import pyarrow.feather as feather
from omegaconf import OmegaConf
from src.data.datatypes import Data
from src.settings import ID_COLUMN, TARGET_COLUMN, TEXT_COLUMN

def _counts(df, cols):
    out = {}
    for c in cols:
        vc = df[c].explode().value_counts()
        out[c] = vc.to_dict()
    return out

def data_pipeline(config: OmegaConf) -> Data:
    d = Path(config.dir)
    data_tbl = feather.read_table(
        d / config.data_filename,
        columns=[ID_COLUMN, TEXT_COLUMN, TARGET_COLUMN, "num_words", "num_targets"] + list(config.code_column_names),
    )
    df = data_tbl.to_pandas()
    splits = feather.read_table(d / config.split_filename).to_pandas()
    if "split" not in splits.columns:
        raise ValueError("Split file must contain a 'split' column.")
    df = df.merge(splits[[ID_COLUMN, "split"]], on=ID_COLUMN, how="inner")

    code_counts = _counts(df, list(config.code_column_names))

    schema = pa.schema([
        pa.field(ID_COLUMN, pa.int64()),
        pa.field(TEXT_COLUMN, pa.large_utf8()),
        pa.field(TARGET_COLUMN, pa.list_(pa.large_string())),
        pa.field("split", pa.large_utf8()),
        pa.field("num_words", pa.int64()),
        pa.field("num_targets", pa.int64()),
    ])
    table = pa.Table.from_pandas(
        df[[ID_COLUMN, TEXT_COLUMN, TARGET_COLUMN, "split", "num_words", "num_targets"]],
        schema=schema, preserve_index=False,
    )
    return Data(table, code_counts)
