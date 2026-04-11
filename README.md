# Name Classification API

## Overview

This project provides a RESTful API endpoint that classifies a given name by integrating with the Genderize API. It processes the external response and returns a structured result based on defined rules, including confidence evaluation.

---


## LANGUAGE
PHP (Laravel)

## Base URL
[http://hng-14.local]http://hng-14.local

---

## Endpoint
GET /api/classify?name={name}

### Query Parameters

| Parameter | Type   | Required | Description      |
|-----------|--------|----------|------------------|
| name      | string | Yes      | Name to classify |

---

## Responses

**Status Code:** `200`

```json
{
  "status": "success",
  "data": {
    "name": "john",
    "gender": "male",
    "probability": 0.99,
    "sample_size": 1234,
    "is_confident": true,
    "processed_at": "2026-04-01T12:00:00Z"
  }
}
```

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
  "message": "Upstream or server failure"
}
```
