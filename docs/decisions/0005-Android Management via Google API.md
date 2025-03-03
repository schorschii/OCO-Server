# Android Management via Google API
Architecture Decision Record  
Lang: en  
Encoding: utf-8  
Date: 2025-02-25  
Author: Georg Sieber

## Decision
Android devices are managed using the Google Android Enterprise API. This allows us to use all features (e.g. managed app store, reading serial numbers od devices).

## Status
Accepted

## Context
It is possible to write an own "agent" app which could manage Android devices after granting device admin rights to it. Unfortunately, this method of management seems deprecated and has major disadvantages in newer Android versions (e.g. device admin apps cannot read device serial numbers anymore).

## Consequences
OCO Android MDM relies on the Google API and infrastructure. But that's the same for iOS management.
