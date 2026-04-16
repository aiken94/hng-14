# Name Classification API

## Overview

This project provides a RESTful API endpoint that classifies a given name by integrating with the Genderize API. It processes the external response and returns a structured result based on defined rules, including confidence evaluation.

---


## LANGUAGE
PHP (Laravel)

## Base URL
https://hng-14-5b38ef29200d.herokuapp.com

## THIRD-PARTY APIs
- [https://agify.io](Agify)
- [https://genderize.io](Genderize)
- [https://nationalize.io](Nationalize)

---

- ## Create Profile
POST /api/profiles

Body Parameters

| Parameter | Type   | Required | Description      |
|-----------|--------|----------|------------------|
| name      | string | Yes      | Name to classify |

---

Responses

**Status Code (New Profile):** `201`

```json
{
    "status": "success",
    "data": {
        "id": "019d96d1-397a-7242-93cd-b420909896d5",
        "name": "grace",
        "gender": "female",
        "gender_probability": 0.99,
        "sample_size": 291069,
        "age": 61,
        "age_group": "senior",
        "country_id": "NG",
        "country_probability": 0.06,
        "created_at": "2026-04-16T15:03:07.000000Z"
    }
}
```
**Status Code (Existing Profile):** `200`

```json
{
    "status": "success",
    "message": "Profile exists",
    "data": {
        "id": "019d96d1-397a-7242-93cd-b420909896d5",
        "name": "grace",
        "gender": "female",
        "gender_probability": 0.99,
        "sample_size": 291069,
        "age": 61,
        "age_group": "senior",
        "country_id": "NG",
        "country_probability": 0.06,
        "created_at": "2026-04-16T15:03:07.000000Z"
    }
}
```
---

- ## Get Single Profile
GET /api/profiles/{id}

**Status Code:** `200`
```json
    {
      "status": "success",
      "data": {
        "id": "019d96c9-997d-7254-b21d-f4c35b75e41c",
        "name": "grace",
        "gender": "female",
        "gender_probability": 0.99,
        "sample_size": 291069,
        "age": 61,
        "age_group": "senior",
        "country_id": "NG",
        "country_probability": 0.06,
        "created_at": "2026-04-16T14:54:47.000000Z"
      }
    }
```

---

- ## Get Single Profile
GET /api/profiles
Optional query parameters: gender, country_id, age_group Query parameter values are case-insensitive (e.g. gender=Male and gender=male are treated the same) Example: /api/profiles?gender=male&country_id=NG

```json
{
  "status": "success",
  "count": 4,
  "data": [
    {
      "id": "019d969b-bac5-731e-bb63-83dd51e24050",
      "name": "ella",
      "gender": "female",
      "age": 53,
      "age_group": "adult",
      "country_id": "CM"
    },
    {
      "id": "019d96a4-9abc-711f-bb0e-b703ce0e55b3",
      "name": "faith",
      "gender": "female",
      "age": 53,
      "age_group": "adult",
      "country_id": "CM"
    },
    {
      "id": "019d96af-0ac5-73f4-93c7-df9cb731f23d",
      "name": "grace",
      "gender": "female",
      "age": 53,
      "age_group": "adult",
      "country_id": "CM"
    },
    {
      "id": "019d96c2-0628-718c-8cea-18c9edc628c4",
      "name": "john",
      "gender": "male",
      "age": 75,
      "age_group": "senior",
      "country_id": "NG"
    }
  ]
}
```
---

- ## Delete Profile
DELETE /api/profiles/{id}

Returns 204 No Content on success.

---

## Error Responses
**Status Code:** `400`

```json
{
  "status": "error",
  "message": "Missing or empty name parameter"
}
```
**Status Code:** `422`

```json
{
  "status": "error",
  "message": "No prediction available for the provided name"
}
```

**Status Code:** `500`

```json
{
    "status": "error",
    "message": "Server failure"
}
```
**Status Code:** `502`

```json
{
  "status": "error",
  "message": "Upstream failure"
}
```


