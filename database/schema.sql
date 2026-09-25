-- Skema database SIAKAD Sekolah
-- {PK} diganti otomatis sesuai driver (SQLite / MySQL) oleh database/install.php

CREATE TABLE settings (
    skey   VARCHAR(50) PRIMARY KEY,
    svalue TEXT
);

CREATE TABLE users (
    id            {PK},
    username      VARCHAR(50)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    name          VARCHAR(100) NOT NULL,
    role          VARCHAR(20)  NOT NULL,
    nip           VARCHAR(30),
    email         VARCHAR(100),
    phone         VARCHAR(30),
    active        INTEGER      NOT NULL DEFAULT 1,
    created_at    DATETIME     DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE classes (
    id                  {PK},
    name                VARCHAR(50) NOT NULL UNIQUE,
    level               INTEGER     NOT NULL,
    homeroom_teacher_id INTEGER NULL,
    FOREIGN KEY (homeroom_teacher_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE subjects (
    id   {PK},
    code VARCHAR(20)  NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    kkm  INTEGER      NOT NULL DEFAULT 75
);

CREATE TABLE students (
    id          {PK},
    user_id     INTEGER     NOT NULL UNIQUE,
    nis         VARCHAR(30) NOT NULL UNIQUE,
    class_id    INTEGER NULL,
    parent_id   INTEGER NULL,
    gender      CHAR(1),
    birth_place VARCHAR(100),
    birth_date  DATE,
    address     TEXT,
    FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
    FOREIGN KEY (class_id)  REFERENCES classes(id) ON DELETE SET NULL,
    FOREIGN KEY (parent_id) REFERENCES users(id)   ON DELETE SET NULL
);

CREATE TABLE teaching_assignments (
    id         {PK},
    teacher_id INTEGER NOT NULL,
    class_id   INTEGER NOT NULL,
    subject_id INTEGER NOT NULL,
    UNIQUE (class_id, subject_id),
    FOREIGN KEY (teacher_id) REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (class_id)   REFERENCES classes(id)  ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

CREATE TABLE schedules (
    id            {PK},
    assignment_id INTEGER    NOT NULL,
    day           INTEGER    NOT NULL,
    start_time    VARCHAR(5) NOT NULL,
    end_time      VARCHAR(5) NOT NULL,
    room          VARCHAR(50),
    FOREIGN KEY (assignment_id) REFERENCES teaching_assignments(id) ON DELETE CASCADE
);

CREATE TABLE attendance (
    id          {PK},
    student_id  INTEGER NOT NULL,
    att_date    DATE    NOT NULL,
    status      CHAR(1) NOT NULL,
    note        VARCHAR(255),
    recorded_by INTEGER NULL,
    UNIQUE (student_id, att_date),
    FOREIGN KEY (student_id)  REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(id)    ON DELETE SET NULL
);

CREATE TABLE grades (
    id            {PK},
    student_id    INTEGER     NOT NULL,
    assignment_id INTEGER     NOT NULL,
    academic_year VARCHAR(9)  NOT NULL,
    semester      VARCHAR(10) NOT NULL,
    task_score    DECIMAL(5,2) NULL,
    mid_score     DECIMAL(5,2) NULL,
    final_score   DECIMAL(5,2) NULL,
    recorded_by   INTEGER NULL,
    updated_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (student_id, assignment_id, academic_year, semester),
    FOREIGN KEY (student_id)    REFERENCES students(id)             ON DELETE CASCADE,
    FOREIGN KEY (assignment_id) REFERENCES teaching_assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by)   REFERENCES users(id)                ON DELETE SET NULL
);

CREATE TABLE report_notes (
    id            {PK},
    student_id    INTEGER     NOT NULL,
    academic_year VARCHAR(9)  NOT NULL,
    semester      VARCHAR(10) NOT NULL,
    note          TEXT,
    updated_by    INTEGER NULL,
    UNIQUE (student_id, academic_year, semester),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(id)    ON DELETE SET NULL
);

CREATE TABLE invoices (
    id         {PK},
    student_id INTEGER       NOT NULL,
    title      VARCHAR(150)  NOT NULL,
    amount     DECIMAL(12,2) NOT NULL,
    due_date   DATE NULL,
    created_by INTEGER NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id)    ON DELETE SET NULL
);

CREATE TABLE payments (
    id          {PK},
    invoice_id  INTEGER       NOT NULL,
    amount      DECIMAL(12,2) NOT NULL,
    paid_at     DATE          NOT NULL,
    method      VARCHAR(30)   NOT NULL,
    note        VARCHAR(255),
    received_by INTEGER NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id)  REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (received_by) REFERENCES users(id)    ON DELETE SET NULL
);

CREATE TABLE announcements (
    id         {PK},
    title      VARCHAR(150) NOT NULL,
    body       TEXT         NOT NULL,
    audience   VARCHAR(20)  NOT NULL DEFAULT 'semua',
    created_by INTEGER NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE letters (
    id            {PK},
    direction     VARCHAR(6)   NOT NULL,
    letter_number VARCHAR(100) NOT NULL,
    letter_date   DATE         NOT NULL,
    party         VARCHAR(150) NOT NULL,
    subject       VARCHAR(200) NOT NULL,
    note          TEXT,
    created_by    INTEGER NULL,
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX idx_users_role        ON users(role);
CREATE INDEX idx_students_class    ON students(class_id);
CREATE INDEX idx_students_parent   ON students(parent_id);
CREATE INDEX idx_attendance_date   ON attendance(att_date);
CREATE INDEX idx_invoices_student  ON invoices(student_id);
CREATE INDEX idx_payments_invoice  ON payments(invoice_id);
