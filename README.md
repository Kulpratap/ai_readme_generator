CONTENTS OF THIS FILE

- Introduction
- Requirements
- Installation
- Recommended modules
- Configuration
- Maintainers

# AI Readme Generator

## Introduction

The AI Readme Generator is a Drupal module that uses Artificial Intelligence to generate README.md files for Drupal modules. With an ability to recognize code structure, it simplifies the often time-consuming task of documentation by auto-generating readable, comprehensive files.

## Requirements

None

## Installation

composer require drupal/ai_readme_generator

## Recommended modules

None

## Configuration

To configure the module after enabling it, follow the steps below:

- Navigate to the module configuration page.
- In the AI Configuration Form, choose AI provider, fill API Key and select module
- In the Generate Readme Form, specify the modules for which README.md files are to be generated.
- You can also generate README.md by using drush command: "drush readme-generator module_machine_name".  

## Maintainers
- Vivek Panicker- [vivek panicker](https://drupal.org/u/vivek-panicker)
- Kul Pratap Singh - [kul.pratap](https://drupal.org/u/kulpratap)
- Arun Sahijpal - [arunsahijpal](https://drupal.org/u/arunsahijpal)
