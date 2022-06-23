USE StoryProducer;

DROP TABLE IF EXISTS Archives;
DROP TABLE IF EXISTS Approvals;
DROP TABLE IF EXISTS Stories;
DROP TABLE IF EXISTS Projects;
DROP TABLE IF EXISTS Consultants;

CREATE TABLE Consultants (
	id       INT           AUTO_INCREMENT primary key,
	name     VARCHAR(256),
	language VARCHAR(256),
	phone    CHAR(10),
	email    VARCHAR(256)  NOT NULL,
	password CHAR(60)      NOT NULL,
	isAdmin  TINYINT       NOT NULL DEFAULT 0
);

CREATE TABLE Projects (
	androidId        CHAR(16)      PRIMARY KEY,
	ethnoCode        CHAR(3)       NOT NULL,
	language         VARCHAR(256),
	country          VARCHAR(256),
	majorityLanguage VARCHAR(256),
	consultantId     INT           NOT NULL REFERENCES Consultants(id),
	trainerEmail     VARCHAR(256),
	email            VARCHAR(256),
	phone            CHAR(10),
	spokenLanguage   VARCHAR(256)

);

CREATE TABLE Stories (
	id        INT           AUTO_INCREMENT PRIMARY KEY,
	title     VARCHAR(256),
	projectId CHAR(16),
	CONSTRAINT UC_Story UNIQUE (title, projectId),
	CONSTRAINT FOREIGN KEY (projectId) REFERENCES Projects(androidId) ON DELETE CASCADE
);

CREATE TABLE Approvals (
	id            INT AUTO_INCREMENT PRIMARY KEY,
	storyId       INT NOT NULL       REFERENCES Stories(id) ON DELETE CASCADE,
	slideNumber   INT NOT NULL,
	slideStatus   INT NOT NULL,
	log           TEXT,
	note          TEXT,
	consultantMsg TEXT,
	translatorMsg TEXT,
	btText        TEXT,
	CONSTRAINT UC_Approval UNIQUE (storyId, slideNumber),
	CONSTRAINT FOREIGN KEY (storyId) REFERENCES Stories(id) ON DELETE CASCADE
);

CREATE TABLE Messages (
	id      INT AUTO_INCREMENT PRIMARY KEY,
	slideId INT NOT NULL,
	message TEXT,
	isTranslator TINYINT NOT NULL,
	CONSTRAINT FOREIGN KEY (slideId) REFERENCES Approvals(id) ON DELETE CASCADE
);
