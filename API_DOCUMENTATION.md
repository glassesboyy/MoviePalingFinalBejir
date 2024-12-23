# Movie Booking API Documentation

## Authentication Endpoints

### Register
- **URL**: `/api/auth/register`
- **Method**: `POST`
- **Body**:
  ```json
  {
    "name": "string",
    "email": "string",
    "password": "string"
  }
  ```
- **Response**: Returns user data and token

### Login
- **URL**: `/api/auth/login`
- **Method**: `POST`
- **Body**:
  ```json
  {
    "email": "string",
    "password": "string"
  }
  ```
- **Response**: Returns authentication token

### Logout
- **URL**: `/api/auth/logout`
- **Method**: `POST`
- **Headers**: Bearer Token required
- **Response**: Logout confirmation message

## Movie Endpoints

### List Movies
- **URL**: `/api/movies`
- **Method**: `GET`
- **Query Parameters**:
  - `per_page` (optional): Number of items per page
- **Response**: Returns paginated list of movies

### Get Movie Detail
- **URL**: `/api/movies/{id}`
- **Method**: `GET`
- **Response**: Returns movie details

## Booking Endpoints

### List Bookings
- **URL**: `/api/booking/list`
- **Method**: `GET`
- **Query Parameters**:
  - `per_page` (optional): Number of items per page
- **Response**: Returns paginated list of bookings with related data

### Get Available Seats
- **URL**: `/api/booking/{scheduleId}`
- **Method**: `GET`
- **Response**: Returns schedule, film, available seats, and services

### Create Booking
- **URL**: `/api/booking`
- **Method**: `POST`
- **Headers**: Bearer Token required
- **Body**:
  ```json
  {
    "schedule_id": "number",
    "seat_id": ["number"],
    "services": ["number"] (optional)
  }
  ```
- **Response**: Returns booking details

### Get Booking Confirmation
- **URL**: `/api/booking/konfirmasi/{scheduleId}`
- **Method**: `GET`
- **Headers**: Bearer Token required
- **Response**: Returns booking confirmation details

### Get Booking Detail
- **URL**: `/api/booking/detail/{id}`
- **Method**: `GET`
- **Response**: Returns detailed booking information

### Update Booking
- **URL**: `/api/booking/{id}`
- **Method**: `PUT`
- **Headers**: Bearer Token required
- **Body**:
  ```json
  {
    "seat_id": ["number"],
    "services": ["number"] (optional)
  }
  ```
- **Response**: Returns updated booking details

### Delete Booking
- **URL**: `/api/booking/{id}`
- **Method**: `DELETE`
- **Headers**: Bearer Token required
- **Response**: Returns success message

## Response Format

### Success Response
```json
{
  "success": true,
  "data": {},
  "message": "Success message"
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error message"
}
```

## Notes
- All dates are in `YYYY-MM-DD` format
- Authentication uses JWT tokens
- Booking modifications are only allowed before the schedule date
- Seat status can be either 'sedia' (available) or 'tidak tersedia' (unavailable)
