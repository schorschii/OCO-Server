# Application Settings in Database
Architecture Decision Record  
Lang: en  
Encoding: utf-8  
Date: 2023-02-07
Author: Georg Sieber

## Decision
Application settings are stored inside the "setting" table in the database.

## Status
Accepted

## Context
This allows flexible automated adjustments on the settings e.g. through updates. In contrast, settings stored as constant in the PHP conf.php must always be adjusted manually by the user if there are changed between the versions. Now, the conf.php only contains the absolute necessary configuration constants such as the database credentials.

## Consequences
The database now contains sensitive information which must be protected, so that they cannot simply be shown by creating a report.
