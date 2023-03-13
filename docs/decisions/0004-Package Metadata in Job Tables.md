# Package Metadata in Job Tables
Architecture Decision Record  
Lang: en  
Encoding: utf-8  
Date: 2023-03-08  
Author: Georg Sieber

## Decision
Package metadata `procedure`, `post_action`, `success_return_codes` and `removes_previous_versions` are stored in the job table (`deployment_rule_job` or `job_container_job`) when creating jobs.

## Status
Accepted

## Context
This ensures consistency between all jobs of a container since the package metadata can be changed at any time.

## Consequences
To apply new `procedure`, `post_action`, `success_return_codes` or `upgrade_behavior` to active deployments, the jobs must be re-created.
