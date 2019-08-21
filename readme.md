## Laravel SDK for PeachPie [![Build Status](https://travis-ci.com/calvinbaart/laravel-peachpie-sample.svg?branch=feature%2Flaravel-sdk)](https://travis-ci.com/calvinbaart/laravel-peachpie-sample)

This project fetches the latest Laravel and compiles it (with some patches) for PeachPie.

## Project Structure

- ``app``: Main project file for the WebServer. Runs AspNetCore.
- ``Laravel``: Laravel with all its dependencies
- ``Laravel.AspNetCore``: AspNetCore extensions for Laravel
- ``Laravel.ComposerDummy``: Dummy project for composer to override some already compiled dependencies
- ``Laravel.Sdk``: Classes for Laravel <-> C# communication.
- ``Laravel.Tests``: PHPUnit tests for Laravel
- ``website``: All the website-specific files, this would be your root folder in a Laravel project

## What does it do?

The code currently contains two run paths. ``App`` and ``Laravel.Tests``. 

Starting app would run a full webserver just like a regular ``Asp.Net`` project. The Webserver redirects all requests to the code in ``website``.

Starting Laravel.Tests would run PHPUnit testing, these tests are the standard tests from the laravel repository.

## Prerequisites

- .NET Core 2.0 or newer
- Optionally - Visual Studio Code 

## How to run the project

1. `dotnet run app`
2. `dotnet run Laravel.Tests`
