---
title: Transaction Types - Overview
---

# Solar Core v3 Transaction Types Overview

This sections describes Mainnet Transaction Types and its structure related to the `serde` process (serialization and deserialization of transactions).

<x-alert type="info">
Transactions are the heart of any blockchain, cryptocurrency or otherwise. They represent a transfer of value from one network participant to another. In SXP, transactions can be of one of multiple types, specified in AIP11, which can affect the content and data structure of each transaction's payload.
</x-alert>

Using the [SXP SDKs](/docs/sdk/), developers can employ the programming language of their choice to build applications utilizing the SXP blockchain. The SXP SDKs are split into two packages for each language: Client and Cryptography.

**Client** SDKs help developers fetch information from the SXP blockchain about its current state: which delegates are currently forging, what transactions are associated with a given wallet, and so on.

**Cryptography** SDKs, by contrast, assist developers in working with transactions: signing, serializing, deserializing, etc.

For more information about SDK implementations visit [SXP SDKs hub](/docs/sdk/).

In the following sections basic transaction types and their structure is presented. If you are interested in the signature generation process and algorithm used, please check the [Cryptography Overview](/docs/core/overview/cryptography) page.

## List of Transaction Types

<livewire:page-reference path="/docs/core/transactions/types/transfer" />

<livewire:page-reference path="/docs/core/transactions/types/second-signature" />

<livewire:page-reference path="/docs/core/transactions/types/delegate-registration" />

<livewire:page-reference path="/docs/core/transactions/types/vote-unvote" />

<livewire:page-reference path="/docs/core/transactions/types/multisignature" />

<livewire:page-reference path="/docs/core/transactions/types/ipfs" />

<livewire:page-reference path="/docs/core/transactions/types/multipayment" />

<livewire:page-reference path="/docs/core/transactions/types/delegate-resignation" />

<livewire:page-reference path="/docs/core/transactions/types/htlc" />
