# Agent-Server Communication Method
Architecture Decision Record
Lang: en
Encoding: utf-8
Date: 2021-09-07
Author: Georg Sieber

## Decision
The agent contacts the server periodically as defined in the agent configuration. There is no open port on the client machine which can be contacted by the server.

## Status
Accepted

## Context
The agent/client should initiate the connection because this means that no port has to be constantly open on the target machine.

This is considered as a security advantage as these client devices (especially notebooks) are often used on different public places/networks where attackers may try to attack the agent when they discover devices with such open ports.

In addition to that, this allows us to deploy software even in foreign networks, where the client is behind an unknown router/firewall. Usually in such public networks, the server cannot reach the client directly. For this reason, the most robust way for these use cases to let the agent contact the server.

## Consequences
Software jobs can not be started directly by the server. You have to wait some seconds until the client contacts the server again. By the default value of 60 seconds, this is not considered as a problem.
