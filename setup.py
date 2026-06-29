from setuptools import setup, find_packages

setup(
    name="drinks-quality-prediction-system",
    version="0.0.0",
    author="Shihab Kaiyum Hossen",
    author_email="shihabkaiyumhossen@gmail.com",
    packages=find_packages(where="src"),
    package_dir={"": "src"},
    install_requires=[],
)