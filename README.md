# Name Classification API

## Overview

This project provides a RESTful API endpoint that classifies a given name by integrating with the Genderize API. It processes the external response and returns a structured result based on defined rules, including confidence evaluation.

---

## LANGUAGE
PHP (Laravel)

## Base URL
https://hng-14-5b38ef29200d.herokuapp.com


# Endpoints

---

- ## Profiles
`GET` /api/profiles

Query Parameters
- gender `int`
- age_group `int`
- country_id `int`
- min_age `int`
- max_age `int`
- min_gender_probability `float`
- min_country_probability `float`
- page `int` default `1`
- limit `int` default `10` maximum `50`
- sort_by `string`
  - age
  - created_at
  - gender_probability
- order `string`
  - asc
  - desc

Example:
`/api/profiles?gender=male&country_id=NG&min_age=25&sort_by=age&order=desc&page=1&limit=10`

Response Code `200`

```json
{
    "status": "success",
    "page": 1,
    "limit": 10,
    "total": 2026,
    "data": [
        {
            "id": "b3f9c1e2-7d4a-4c91-9c2a-1f0a8e5b6d12",
            "name": "emmanuel",
            "gender": "male",
            "gender_probability": 0.99,
            "age": 34,
            "age_group": "adult",
            "country_id": "NG",
            "country_name": "Nigeria",
            "country_probability": 0.85,
            "created_at": "2026-04-01T12:00:00Z"
        }
    ]
}
```

- ## Natural Language
`GET` /api/profiles/search

Query Parameters
- q `string`
- page `int` default `1`
- limit `int` default `10` maximum `50`

Example:
`/api/profiles/search?q=young males from nigeria`

Response Code `200`
```json
{
    "status": "success",
    "page": 1,
    "limit": 10,
    "total": 2026,
    "data": [
        {
            "id": "b3f9c1e2-7d4a-4c91-9c2a-1f0a8e5b6d12",
            "name": "emmanuel",
            "gender": "male",
            "gender_probability": 0.99,
            "age": 34,
            "age_group": "adult",
            "country_id": "NG",
            "country_name": "Nigeria",
            "country_probability": 0.85,
            "created_at": "2026-04-01T12:00:00Z"
        }
    ]
}
```

- ## Get Single Profile
`GET` /api/profiles/{id}

**Status Code:** `200`
```json
{
    "id": "b3f9c1e2-7d4a-4c91-9c2a-1f0a8e5b6d12",
    "name": "emmanuel",
    "gender": "male",
    "gender_probability": 0.99,
    "age": 34,
    "age_group": "adult",
    "country_id": "NG",
    "country_name": "Nigeria",
    "country_probability": 0.85,
    "created_at": "2026-04-01T12:00:00Z"
}
```

- ## Delete Profile
`DELETE` /api/profiles/{id}

Returns 204 No Content on success.

## Error Responses
- **Status Code:** `400`

```json
{
  "status": "error",
  "message": "Missing or empty name parameter"
}
```

- **Status Code:** `404`

```json
{
  "status": "error",
  "message": "Profile not found"
}
```

- **Status Code:** `422`

```json
{
  "status": "error",
  "message": "No prediction available for the provided name"
}
```

- **Status Code:** `500`

```json
{
    "status": "error",
    "message": "Server failure"
}
```
- **Status Code:** `502`

```json
{
  "status": "error",
  "message": "Upstream failure"
}
```


