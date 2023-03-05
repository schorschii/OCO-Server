# Author as Text in Database
Architecture Decision Record  
Lang: en  
Encoding: utf-8  
Date: 2021-11-10  
Updated: 2023-03-05  
Author: Georg Sieber

## Decision
The username in the "log" table is stored as text. It is not linked with the ID from the system user table.

## Status
Accepted, Updated

## Context
Logs should contain a reference to the user which executed a specific action until logs are cleaned up, even after people leave the company and their accounts are deleted.

## Consequences
The login usernames are saved redundantly in the "log" table.
