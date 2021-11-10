# Author as Text in Database
Architecture Decision Record  
Lang: en  
Encoding: utf-8  
Date: 2021-11-10  
Author: Georg Sieber

## Decision
The creator of a package, job container etc. is saved in the "author" field in the corresponding database table as text, containing the login username. It is not linked with the ID from the system user table.

## Status
Accepted

## Context
System user accounts should be deletable if the employee leaves the company. But the information, who created the package, should optionally be retained. If this is not desired, the "author" column can be set e.g. to "anonymous" when the employee is leaving with a simple SQL query:
```
UPDATE package SET author = 'anonymous' WHERE author = 'employee123';
```

## Consequences
The author login username is saved redundantly. If the username changes, every package record in the database needs to be updated with the SQL command described above.
