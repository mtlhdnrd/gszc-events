CREATE DATABASE IF NOT EXISTS bgszc_events;

USE bgszc_events;

CREATE TABLE schools (
    `school_id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `address` VARCHAR(255) NOT NULL
);

CREATE TABLE teachers (
    `teacher_id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(130) NOT NULL,
    `phone` VARCHAR(20) NOT NULL
);

CREATE TABLE users (
    `user_id` INT PRIMARY KEY AUTO_INCREMENT,
    `username` VARCHAR(255) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL
);

CREATE TABLE students (
    `user_id` INT PRIMARY KEY NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) UNIQUE NOT NULL,
    `teacher_id` INT NOT NULL,
    `school_id` INT NOT NULL,
    `total_hours_worked` INT DEFAULT 0,
    FOREIGN KEY (`teacher_id`) REFERENCES teachers(`teacher_id`),
    FOREIGN KEY (`school_id`) REFERENCES schools(`school_id`),
    FOREIGN KEY (`user_id`) REFERENCES users(`user_id`)
);

CREATE TABLE admins (
    `user_id` INT PRIMARY KEY NOT NULL,
    FOREIGN KEY (`user_id`) REFERENCES users(`user_id`)
);

CREATE TABLE events (
    `event_id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `date` DATE NOT NULL,
    `location` VARCHAR(255) NOT NULL,
     -- pending/ready/failed (based on if enough people accepted it or not)
    `status` VARCHAR(255) NOT NULL,
    `busyness` ENUM('low', 'high') NOT NULL
);

-- the name workshop doesn't ring quite right, but I can't think of a better one
CREATE TABLE workshops (
    `workshop_id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(130) NOT NULL,
    `description` VARCHAR(255)
);

CREATE TABLE event_workshop (
    `event_workshop_id` INT PRIMARY KEY AUTO_INCREMENT,
    `event_id` INT NOT NULL,
    `workshop_id` INT NOT NULL,
    `max_workable_hours` INT NOT NULL,
    `number_of_mentors_required` INT NOT NULL,
    FOREIGN KEY (`event_id`) REFERENCES events(`event_id`),
    FOREIGN KEY (`workshop_id`) REFERENCES workshops(`workshop_id`)
);

-- Diak foglalkozas
CREATE TABLE mentor_workshop (
    `mentor_workshop_id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `workshop_id` INT NOT NULL,
    FOREIGN KEY (`user_id`) REFERENCES students(`user_id`),
    FOREIGN KEY (`workshop_id`) REFERENCES workshops(`workshop_id`)
);

CREATE TABLE rankings (
    `ranking_id` INT PRIMARY KEY AUTO_INCREMENT,
    `event_workshop_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `ranking_number` INT NOT NULL,
    FOREIGN KEY (`event_workshop_id`) REFERENCES event_workshop(`event_workshop_id`),
    FOREIGN KEY (`user_id`) REFERENCES students(`user_id`)
);

-- Diak meghivo -> egy darab meghívó a diák részére
CREATE TABLE student_invitations (
    `invitation_id` INT PRIMARY KEY AUTO_INCREMENT,
    `event_workshop_id` INT NOT NULL,
    `user_id` INT NOT NULL,
     -- pending/accepted/refused/re-accepted
    `status` VARCHAR(50) NOT NULL,
    FOREIGN KEY (`event_workshop_id`) REFERENCES event_workshop(`event_workshop_id`),
    FOREIGN KEY (`user_id`) REFERENCES students(`user_id`)
);

CREATE TABLE attendance_sheets (
    `attendance_id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `event_workshop_id` INT NOT NULL,
    `note` TEXT NOT NULL,
    FOREIGN KEY (`user_id`) REFERENCES students(`user_id`),
    FOREIGN KEY (`event_workshop_id`) REFERENCES event_workshop(`event_workshop_id`)
);
