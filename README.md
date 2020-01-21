# ![Laravel Example App](https://sandy.capeandbay.com/wp-content/uploads/2019/09/CapeBay_Anchor-Blue-150x150.png)

[![Build Status](https://img.shields.io/travis/gothinkster/laravel-realworld-example-app/master.svg)](https://travis-ci.org/gothinkster/laravel-realworld-example-app) 

> ### Source-code for the publically-accessible Microservice for interfacing with the leadBinder platform.

Production URL - https://lb-api.capeandbay.com

Staging URL - https://lb-api-stage.capeandbay.com

Development URL - https://lb-api-dev.capeandbay.com 

Last Update - January 13, 2020.

----------

# Getting started

## Installation

Please check the official laravel installation guide for server requirements before you start. [Official Documentation](https://laravel.com/docs/5.4/installation#installation)

Clone the repository

    git clone git@bitbucket.org:capeandbaytrufit/leadbinder-public-api.git
    
Switch to the repo folder

    cd leadbinder-public-api
    
Install all the dependencies using composer

    composer install
    
Copy the example env file and make the required configuration changes in the .env file
    
        cp .env.example .env
        
Generate a new application key

    php artisan key:generate
    
Run the database migrations (**Set the database connection in .env before migrating**)

    php artisan migrate
    
**TL;DR command list**

    git clone git@bitbucket.org:capeandbaytrufit/leadbinder-public-api.git
    cd leadbinder-public-api
    composer install
    cp .env.example .env
    php artisan key:generate
    
**Make sure you set the correct database connection information before running the migrations** [Environment variables](#environment-variables)

    php artisan migrate  

## API Specification

This application adheres to the api specifications set by the [Cape & Bay](https://github.com/capeandbay-devs) team. This helps mix and match any backend with any other frontend without conflicts.

> [Full API Spec](https://bitbucket.org/capeandbaytrufit/leadbinder-public-api/src/master/README.md)

More information regarding the project can be found here https://bitbucket.org/capeandbaytrufit/leadbinder-public-api

----------

# Code overview

## Dependencies


## Folders

## Environment variables

- `.env` - Environment variables can be set in this file

***Note*** : You can quickly set the database information and other variables in this file and have the application fully working.

----------

# Testing API

----------
 
# Authentication

----------

# Cross-Origin Resource Sharing (CORS)

----------
Copyright 2020. Cape & Bay, LLC. 
