# BACKEND_README: API Design for QR Code Attendance System

This document describes the endpoints, authentication, and data models required for a backend API that provides the same functionalities as the current PHP QR Code Attendance System.

## 1. Authentication
- **POST /auth/login**: Authenticate user (professor or student) and return a JWT or session token.
- **POST /auth/logout**: Invalidate the session or token.
- **POST /auth/register** (optional): Register a new user (if self-registration is allowed).

## 2. Professor Endpoints
- **GET /professor/classes**: List all classes for the authenticated professor.
- **POST /professor/classes**: Create a new class (turma).
- **GET /professor/classes/{class_id}**: Get details of a specific class, including students and attendance stats.
- **POST /professor/classes/{class_id}/days**: Register a new class day (aula) for a class.
- **GET /professor/classes/{class_id}/days**: List all class days for a class.
- **GET /professor/classes/{class_id}/days/{day_id}**: Get details for a specific class day (including attendance list).
- **POST /professor/classes/{class_id}/days/{day_id}/qrcode**: Generate a QR code for attendance (returns QR data or image).
- **POST /professor/classes/{class_id}/students**: Add a student to a class.
- **DELETE /professor/classes/{class_id}/students/{student_id}**: Remove a student from a class.
- **GET /professor/stats**: Get dashboard statistics (total classes, students, today's classes, etc).

## 3. Student Endpoints
- **GET /student/classes**: List all classes the student is enrolled in.
- **GET /student/classes/{class_id}**: Get details of a class.
- **GET /student/classes/{class_id}/days**: List all class days for a class.
- **POST /student/attendance**: Register attendance by submitting QR code data (or manual/facial recognition in the future).
- **GET /student/attendance/history**: Get attendance history for the student.

## 4. Attendance
- **POST /attendance/mark**: Mark attendance for a student (QR/manual/facial recognition).
- **GET /attendance/{day_id}/list**: Get the list of students present for a specific class day.

## 5. QR Code
- **POST /qrcode/generate**: Generate QR code data for a class day (professor only).
- **POST /qrcode/scan**: Student submits scanned QR code data to mark attendance.

## 6. Admin (optional)
- **GET /admin/users**: List all users.
- **GET /admin/classes**: List all classes.
- **GET /admin/attendance**: List all attendance records.

## 7. Data Models (Entities)
- **User**: id, name, email, password_hash, role (professor/student)
- **Class (Turma)**: id, name, discipline_id, year, created_at
- **Discipline**: id, name
- **ClassDay (DiaDeAula)**: id, class_id, date, professor_id
- **Attendance**: id, class_day_id, student_id, timestamp
- **ClassMembership**: id, class_id, user_id, role (professor/student)

## 8. Security  
- Left for future implementations

## 9. Error Handling
- Return appropriate HTTP status codes (401 Unauthorized, 403 Forbidden, 404 Not Found, 400 Bad Request, 500 Server Error).
- Return error messages in JSON format.

## 10. Future Extensions
- **Facial Recognition**: Endpoint for uploading/processing face images.
- **Manual Attendance**: Endpoint for professors to manually mark students present.
- **Notifications**: Endpoint for sending notifications to students/professors.

---

This API design covers all the core features of the PHP system, including authentication, class and attendance management, QR code generation, and statistics. It is suitable for implementation in frameworks like FastAPI (Python), Express (Node.js), or Laravel (PHP).
