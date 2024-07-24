# Library Management System

## Project Description

This project is a Library Management System API built using Symfony and follows Domain-Driven Design principles.

## Installation Instructions

1. Clone the repository.
2. Install dependencies:
   `composer install`
3. Set up the database:
   `php bin/console doctrine:database:create`
   `php bin/console doctrine:schema:update --force`
   `php bin/console doctrine:fixtures:load`
4. Run the Symfony server
   `symfony server:start`
5. To access API documentation
   `http://127.0.0.1:8000/api/doc`
6. To run test cases
   `php vendor/bin/phpunit`

## API Documentation

# Users

- Create User: POST /api/users
- Get User by ID: GET /api/users/{id}
- Update User: PUT /api/users/{id}
- Delete User: DELETE /api/users/{id}
- Get All Users: GET /api/users
- Borrow Book: POST /api/users/{userId}/borrow/{bookId}
- Return Book: POST /api/users/{userId}/return/{bookId}

# Books

- Create Book: POST /api/books
- Get Book by ID: GET /api/books/{id}
- Update Book: PUT /api/books/{id}
- Delete Book: DELETE /api/books/{id}
- Get All Books: GET /api/books

# Testing Instructions

- Use the provided Postman collection to test the API endpoints.

## Future Considerations

- Implement user-specific borrowing limits.
- Add more detailed logging and error handling.
- Improve security and add authentication.
