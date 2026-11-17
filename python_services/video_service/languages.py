"""Per-language settings: labels, spoken names, file names and syntax colouring rules."""

from __future__ import annotations

import builtins
import keyword
import re
from dataclasses import dataclass
from functools import lru_cache

HASH_COMMENT = r"\#[^\n]*"
SLASH_COMMENT = r"//[^\n]*|/\*[\s\S]*?\*/"
DASH_COMMENT = r"--[^\n]*|/\*[\s\S]*?\*/"

PYTHON_STRING = r"""[rbfRBF]{0,2}(?:\"\"\"[\s\S]*?\"\"\"|'''[\s\S]*?'''|"(?:\\.|[^"\\\n])*"|'(?:\\.|[^'\\\n])*')"""
COMMON_STRING = r"""\"(?:\\.|[^"\\\n])*\"|'(?:\\.|[^'\\\n])*'"""
BACKTICK_STRING = r"""`(?:\\.|[^`\\])*`"""


@dataclass(frozen=True)
class Language:
    key: str
    label: str
    spoken: str
    filename: str
    keywords: frozenset[str]
    comment: str
    string: str
    builtins: frozenset[str] = frozenset()
    ignore_case: bool = False


def _words(text: str) -> frozenset[str]:
    return frozenset(text.split())


JS_KEYWORDS = (
    "break case catch class const continue debugger default delete do else export extends finally for "
    "function if import in instanceof let new return super switch this throw try typeof var void while "
    "with yield async await of true false null undefined"
)

LANGUAGES: dict[str, Language] = {
    "python": Language(
        "python", "Python", "Python", "main.py",
        _words(" ".join(keyword.kwlist) + " True False None"),
        HASH_COMMENT, PYTHON_STRING,
        builtins=frozenset(name for name in dir(builtins) if not name.startswith("_")),
    ),
    "javascript": Language(
        "javascript", "JavaScript", "JavaScript", "index.js", _words(JS_KEYWORDS), SLASH_COMMENT,
        COMMON_STRING + "|" + BACKTICK_STRING,
    ),
    "typescript": Language(
        "typescript", "TypeScript", "TypeScript", "index.ts",
        _words(JS_KEYWORDS + " interface type enum implements private public protected readonly namespace "
               "declare abstract as number string boolean any unknown never"),
        SLASH_COMMENT, COMMON_STRING + "|" + BACKTICK_STRING,
    ),
    "php": Language(
        "php", "PHP", "P H P", "index.php",
        _words("abstract and array as break case catch class clone const continue default do echo else elseif "
               "empty extends final finally fn for foreach function global if implements include instanceof "
               "interface isset list match namespace new or print private protected public readonly require "
               "return static switch throw trait try unset use var while xor yield true false null"),
        SLASH_COMMENT + "|" + HASH_COMMENT, COMMON_STRING,
    ),
    "java": Language(
        "java", "Java", "Java", "Main.java",
        _words("abstract assert boolean break byte case catch char class const continue default do double else "
               "enum extends final finally float for if implements import instanceof int interface long new "
               "package private protected public return short static super switch this throw throws try void "
               "volatile while var true false null"),
        SLASH_COMMENT, COMMON_STRING,
    ),
    "csharp": Language(
        "csharp", "C#", "C sharp", "Program.cs",
        _words("abstract as async await bool break byte case catch char class const continue decimal default "
               "do double else enum finally float for foreach if in int interface is long namespace new null "
               "object override private protected public readonly return short static string struct switch "
               "this throw try using var virtual void while true false"),
        SLASH_COMMENT, COMMON_STRING,
    ),
    "cpp": Language(
        "cpp", "C++", "C plus plus", "main.cpp",
        _words("auto bool break case catch char class const continue default delete do double else enum "
               "explicit extern false float for friend goto if inline int long namespace new nullptr operator "
               "private protected public return short signed sizeof static struct switch template this throw "
               "true try typedef typename union unsigned using virtual void volatile while"),
        SLASH_COMMENT, COMMON_STRING,
    ),
    "go": Language(
        "go", "Go", "Go", "main.go",
        _words("break case chan const continue default defer else fallthrough for func go goto if import "
               "interface map package range return select struct switch type var true false nil"),
        SLASH_COMMENT, COMMON_STRING + "|" + BACKTICK_STRING,
    ),
    "sql": Language(
        "sql", "SQL", "S Q L", "query.sql",
        _words("select from where group by order having insert into values update set delete create table "
               "alter drop join inner left right outer on as and or not null is in like limit distinct union "
               "all case when then else end"),
        DASH_COMMENT, r"""'(?:''|[^'])*'""", ignore_case=True,
    ),
}


def get_language(key: str | None) -> Language:
    """Known language, or a plain fallback so an unknown key never breaks a render."""
    key = (key or "python").lower()
    if key in LANGUAGES:
        return LANGUAGES[key]
    return Language(key, key.title(), key.title(), "main.txt", frozenset(), HASH_COMMENT, COMMON_STRING)


@lru_cache(maxsize=None)
def token_pattern(key: str) -> re.Pattern[str]:
    language = get_language(key)
    return re.compile(
        rf"(?P<comment>{language.comment})"
        rf"|(?P<string>{language.string})"
        r"|(?P<number>\b\d+(?:\.\d+)?\b)"
        r"|(?P<name>[A-Za-z_]\w*)"
        r"|(?P<other>\s|.)"
    )
