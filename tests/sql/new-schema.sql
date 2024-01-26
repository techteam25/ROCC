-- SQLite does not support IF EXISTS in DROP TABLE, so we use a PRAGMA
PRAGMA foreign_keys=off;

DROP TABLE IF EXISTS Messages;
DROP TABLE IF EXISTS Slide;
DROP TABLE IF EXISTS Stories;
DROP TABLE IF EXISTS Assigned;
DROP TABLE IF EXISTS Projects;
DROP TABLE IF EXISTS Consultants;

-- enable foreign key constraints
PRAGMA foreign_keys=on;

-- Projects
-- Stories
-- Slide
-- Consultants
-- Assigned

CREATE TABLE Consultants (
  id INTEGER PRIMARY KEY,
  name VARCHAR(256),
  language VARCHAR(256),
  phone CHAR(10),
  email VARCHAR(256) UNIQUE NOT NULL,
  password CHAR(60) NOT NULL,
  isAdmin BOOLEAN NOT NULL DEFAULT 0
);

CREATE TABLE Projects (
  id INTEGER PRIMARY KEY,
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
--
CREATE TABLE Assigned (
    ConsultantId INTEGER NOT NULL,
    ProjectId INTEGER NOT NULL,
    UNIQUE (ConsultantId, ProjectId)
);

CREATE TABLE Stories (
  id INTEGER PRIMARY KEY,
  title VARCHAR(256),
  `FirstThreshold` datetime DEFAULT NULL,
  `SecondThreshold` datetime DEFAULT NULL,
  language VARCHAR(256),
  projectId INTEGER NOT NULL,
  note TEXT NOT NULL,
  UNIQUE (title, projectId)
  FOREIGN KEY (projectId) REFERENCES Projects(id)
);

CREATE TABLE Slide (
  id INTEGER PRIMARY KEY,
  storyId INTEGER NOT NULL REFERENCES Stories(id),
  note TEXT NOT NULL,
  slideNumber INTEGER NOT NULL,
  isApproved BOOLEAN NOT NULL,
  lastApprovalChangeTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (storyId, slideNumber)
  FOREIGN KEY (storyId) REFERENCES Stories(id)
);