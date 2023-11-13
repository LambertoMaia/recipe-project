from dataclasses import dataclass
import json


@dataclass
class Config:
    url: str
    params: list


def load_config(path: str) -> Config:
    with open(path, "r") as f:
        data = json.load(f)
        return Config(**data)
