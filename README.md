<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400"></a></p>

<p align="center">
<a href="https://travis-ci.org/laravel/framework"><img src="https://travis-ci.org/laravel/framework.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Project

This is a backend project created in php (Laravel) and using containerization in Docker. The purpose of the application is to help manage projects, project tasks, and teams consisting of company employees.

## Database

This project uses a connection to a PostgreSQL database. The project includes typical CRUD operations on data. All database queries were made using REST API. The database consists of 5 tables connected relationally:
-user table
-projects table
-task table
-comments table

Each project has its own description, time to complete and owner, then in the teams table, employees working on this project can be assigned to each project. The task table stores tasks that need to be performed within a given project. The comments table allows you to assign comments to each task.

 Passwords in the database are stored in a safe way - hashed

## JWT Token

The ability to perform operations on data is only available to users who have a created account in the user database, who must also be logged in and their token must be active. The JWT (JSON Web Tokens) mechanism is used

