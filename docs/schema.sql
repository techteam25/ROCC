USE StoryProducer;

DROP TABLE Archives;
DROP TABLE Approvals;
DROP TABLE Stories;
DROP TABLE Projects;
DROP TABLE Consultants;

CREATE TABLE Consultants (
	id       INT           IDENTITY(1,1) primary key,
	name     VARCHAR(256),
	language VARCHAR(256),
	phone    CHAR(10),
	email    VARCHAR(256)  NOT NULL,
	password CHAR(60)      NOT NULL,
	isAdmin  BIT           NOT NULL DEFAULT 0
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
	id        INT           IDENTITY(1,1) PRIMARY KEY,
	title     VARCHAR(256),
	projectId CHAR(16)      REFERENCES Projects(androidId),
	notes VARCHAR(max),
	UNIQUE (title, projectId)
);

CREATE TABLE Approvals (
	id          INT IDENTITY(1,1) PRIMARY KEY,
	storyId     INT NOT NULL      REFERENCES Stories(id),
	slideNumber INT NOT NULL,
	slideStatus INT NOT NULL,
	log         VARCHAR(max),
	UNIQUE (storyId, slideNumber)
);

CREATE TABLE Archives (
	id        INT          IDENTITY(1,1) PRIMARY KEY,
	storyId   INT          NOT NULL      REFERENCES Stories(id),
	videoURL  VARCHAR(256) NOT NULL
);