DROP TABLE IF EXISTS Messages;
DROP TABLE IF EXISTS Slide;
DROP TABLE IF EXISTS Stories;
DROP TABLE IF EXISTS Assigned;
DROP TABLE IF EXISTS Projects;
DROP TABLE IF EXISTS Consultants;

CREATE TABLE Consultants (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(256),
  language VARCHAR(256),
  phone CHAR(10),
  email VARCHAR(256) UNIQUE NOT NULL,
  password CHAR(60) NOT NULL,
  isAdmin BOOLEAN NOT NULL DEFAULT 0
);

CREATE TABLE Projects (
  id INT PRIMARY KEY AUTO_INCREMENT,
  androidId CHAR(16) NOT NULL UNIQUE,
  ethnoCode VARCHAR(256) NOT NULL,
  language VARCHAR(256),
  country VARCHAR(256),
  majorityLanguage VARCHAR(256),
  trainerEmail VARCHAR(256),
  email VARCHAR(256),
  phone VARCHAR(256),
  spokenLanguage VARCHAR(256)
);

CREATE TABLE Assigned (
    ConsultantId INT NOT NULL,
    ProjectId INT NOT NULL,
    UNIQUE (ConsultantId, ProjectId)
);

CREATE TABLE Stories (
  id INT PRIMARY KEY AUTO_INCREMENT,
  title VARCHAR(256),
  `FirstThreshold` datetime DEFAULT NULL,
  `SecondThreshold` datetime DEFAULT NULL,
  language VARCHAR(256),
  projectId INT NOT NULL REFERENCES Projects(id),
  note TEXT NOT NULL,
  UNIQUE (title, projectId)
);

CREATE TABLE Slide (
  id INT PRIMARY KEY AUTO_INCREMENT,
  storyId INT NOT NULL REFERENCES Stories(id),
  note TEXT NOT NULL,
  slideNumber INT NOT NULL,
  isApproved BOOLEAN NOT NULL,
  lastApprovalChangeTime TIMESTAMP DEFAULT NOW(),
  UNIQUE (storyId, slideNumber)
);

CREATE TABLE Messages (
  id INT PRIMARY KEY AUTO_INCREMENT,
  storyId INT NOT NULL REFERENCES Stories(id),
  slideNumber INT NOT NULL, 
  isConsultant BOOLEAN NOT NULL,
  isUnread BOOLEAN NOT NULL,
  isTranscript BOOLEAN NOT NULL,
  timeSent TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  text TEXT NOT NULL
);
