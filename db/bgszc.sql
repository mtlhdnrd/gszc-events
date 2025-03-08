CREATE DATABASE IF NOT EXISTS bgszc_events;

USE bgszc_events;

CREATE TABLE school (
    `school_id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `address` VARCHAR(255) NOT NULL
);

CREATE TABLE teacher (
    `teacher_id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(130) NOT NULL,
    `phone` VARCHAR(20) NOT NULL
);

CREATE TABLE user (
    `user_id` INT PRIMARY KEY AUTO_INCREMENT,
    `username` VARCHAR(255) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL
);

CREATE TABLE student (
    `user_id` INT PRIMARY KEY NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) UNIQUE NOT NULL,
    `teacher_id` INT NOT NULL,
    `school_id` INT NOT NULL,
    `total_hours_worked` INT DEFAULT 0,
    FOREIGN KEY (`teacher_id`) REFERENCES teacher(`teacher_id`),
    FOREIGN KEY (`school_id`) REFERENCES school(`school_id`),
    FOREIGN KEY (`user_id`) REFERENCES user(`user_id`)
);

CREATE TABLE admin (
    `user_id` INT PRIMARY KEY NOT NULL,
    FOREIGN KEY (`user_id`) REFERENCES user(`user_id`)
);

CREATE TABLE event (
    `event_id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `date` DATE NOT NULL,
    `location` VARCHAR(255) NOT NULL,
    `status` VARCHAR(255) NOT NULL, -- pending/ready/failed (based on if enough people accepted it or not)
    `busyness` ENUM('low', 'high') NOT NULL
);

-- the name workshop doesn't ring quite right, but I can't think of a better one
CREATE TABLE workshop (
    `workshop_id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(130) NOT NULL,
    `description` VARCHAR(255)
);

CREATE TABLE event_workshop (
    `event_workshop_id` INT PRIMARY KEY AUTO_INCREMENT,
    `event_id` INT,
    `workshop_id` INT,
    `max_workable_hours` INT NOT NULL,
    `number_of_mentors_required` INT NOT NULL,
    FOREIGN KEY (`event_id`) REFERENCES event(`event_id`),
    FOREIGN KEY (`workshop_id`) REFERENCES workshop(`workshop_id`)
);

--Diak foglalkozas
CREATE TABLE mentor_workshops ( 
    `mentor_workshop_id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT,
    `workshop_id` INT,
    FOREIGN KEY (`user_id`) REFERENCES student(`user_id`),
    FOREIGN KEY (`workshop_id`) REFERENCES workshop(`workshop_id`)
);

CREATE TABLE ranking (
    `ranking_id` INT PRIMARY KEY AUTO_INCREMENT,
    `event_workshop_id` INT,
    `user_id` INT,
    `ranking_number` INT NOT NULL,
    FOREIGN KEY (`event_workshop_id`) REFERENCES event_workshop(`event_workshop_id`),
    FOREIGN KEY (`user_id`) REFERENCES student(`user_id`)
);

--Diak meghivo -> egy darab meghívó a diák részére
CREATE TABLE student_invitation(
    `invitation_id` INT PRIMARY KEY AUTO_INCREMENT,
    `event_workshop_id` INT,
    `user_id` INT,
    `status` VARCHAR(50) --pending/accepted/refused/re-accepted/expired
);

CREATE TABLE attendance_sheet (
    `attendance_id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT,
    `event_workshop_id` INT,
    `note` TEXT,
    FOREIGN KEY (`user_id`) REFERENCES student(`user_id`),
    FOREIGN KEY (`event_workshop_id`) REFERENCES event_workshop(`event_workshop_id`)
);