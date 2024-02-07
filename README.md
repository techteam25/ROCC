# Remote Oral Consultancy Checker (ROCC)

This is a companion to the StoryProducer Mobile App. The mobile app will likely
be being used by someone in the place for whom a story is being translated; in
contrast, this web app is for consultants to give feedback and approval for
translations made by the mobile app. Usually the consultant does not know that
target language, so the app must support an appropriate workflow.

The ROCC becomes involved in the translation process during the
accuracy-checking phase of translation. When there is no on-site consultant who
has been trained in translation principles, the accuracy-checking must be done
remotely.

There are three phases to accuracy checking when the ROCC is involved.
1. Story Back - someone who hasn't been involved in the translation listens to
	 the entire story as many times as needed, and retells the story back in
	 English. The goal is not to be entirely accurate, but just to get the basic
	 parts of the story right. This stage requires the whole audio file for the
	 story.
2. Translate Back - on the mobile app, the translator records, potentially in
	 small pieces, the English audio for each single slide in the story. The ROCC
	 only receives a single audio file for this slide however. The consultant can
	 listen to these slide recordings to see if they make sense. Recall that
	 consultants may not know the language, so the only information they have is
	 in English. In this stage, the consultant can validate each slides
	 translation against the "Story Back" phase's big picture translation for
	 anomalies.
3. Accuracy Check - in this stage, the consultant and translator can chat with
	 each other about issues that might have arisen during translation. Each
	 slide has it's own chat window, and there is also a global chat window for
	 any other discussion. The chat window should have read receipts of some
	 form, perhaps explicitly send with a "resolve" button, or implicitly sent by
	 simple visiting the page in the browser. The translator cannot continue with
	 translation until the consultant sends an approval for every slide in the
	 story.

# Development

Developed by 2017-2018 Cedarville University CS Senior Design team
with continuing development by 2018-2019 CS Senior Design team.

Currently being developed by 2019-2020 CS Senior Design Team.

# Setup

The project uses [Composer](getcomposer.org), a package manager for php
(eventually we may remove the need for any dependencies, but for now this is
necessary). The linked site has information on how to [Download and
install](getcomposer.org/download).

To setup the project,
1. Clone this repository and `cd` into the new directory.
2. Run `php composer.phar install` from within the newly cloned directory. You
	 may need to modify this command based on how you installed composer.
3. Run `find . -type f -exec chmod 644 -- {} +`, `find . -type d -exec chmod
   755 -- {} +`, and `chmod Files -type d -exec chmod 777 -- {} +` *from within
   your clone directory* to make directories and files accessible to the web
   server. Be careful that you do not run this command somewhere unintended. So
   that you do not have to remember or re-copy these commands frequently, there
   is a script called `reset_file_permissions.sh` which you can will update
   file permissions for you.
4. Edit the variables in `API/utils/ConnectionSettings.php` to match the
   correct values for your database.

# Database Setup and Reloading

For any development setup, there will be an SQL database that the server
interacts with. In this repository, there is a `docs/new-schema.sql` file that
contains SQL to delete existing tables and create new tables. In addition,
there is a `docs/seed.sql` file which contains SQL statements to add some
testing data to the database.

During testing and development, likely the database will change due to
interaction with the database. If some things happen that put the database in a
bad state, or for some reason you want to reset the database back to how it
started, there is a file called `rebuild-db.sh`. To run it, you need to specify
the username and database that you want to reload; that is, you would execute a
command of the form `sh rebuild-db.sh username password` substituting the
appropriate things for `username` and `password` of course.

If you have more data that you think is useful to always have around for
development, make sure to edit the `docs/seed.sql` file and add some `INSERT`
statements. Otherwise, the next time someone runs `rebuild-db.sh`, all data
will be deleted and reset to only what is described in `docs/seed.sql`.

# Feature development

Follow these guidelines as much as possible:

* For every change in the repo, make a new branch for the feature with a name
	describing the feature. (i.e. Don't name it `my-branch-3`)
* Work on small features; if a feature is very large, figure out how to make it
	into smaller features. This makes it possible for you to push your code more
	quickly back into the main `develop` branch; in addition, it also makes it
	easier for other team members to review and understand your changes.
* Branch directly off of `develop` mostly. If you have a feature which has not
	yet been merged into `develop` and a second feature which depends on this
	feature, don't fret! Just branch from the feature that is waiting to be
	merged.
* Keep changes within a branch mostly unified around the feature that the
	branch is about.
* `develop` should always be in a working state.

# Design and Code Structure

## Phone Data Storage

The phone keeps track of the directory containing all the templates. When work 
begins to translate a specific template, the app creates a `project/` directory 
inside the template's directory. Inside this `project/` directory, there is a 
`story.json` file that gets created. This file stores all the data related to 
upload status, and what the names of back translation audio files is; in fact, 
it stores everything related to the story, including the information that the 
template's `project.xml` contained. Thus, there is no proper database and SQLite
usage, but rather an auto-saved JSON file.

## General Structure

MySQL database holds user, template, and project info. Web application intended 
to be run on Apache Web Server. HTML/CSS/JS frontend with PHP api and backend 
and MySQL DB


## Testing

### Prerequisite

Before running the tests, ensure that the following prerequisites are met:

The tests depend on specific values retrieved from the `API/utils/ConnectionSettings.php` file:
* Utilize the `DB_DNS` constant in the file for the database name and server host information in the database connection.
* Access the global variables `$databaseUser` and `$databasePassword` for authentication.
* `$filesRoot` variable for Root file location in order to write story data

#### Database connection:

Tests used the database connection settings `API/utils/ConnectionSettings.php` file.

#### Root files
`$filesRoot` variable from `API/utils/ConnectionSettings.php` file is used by tests

#### SQL Files:
* The tests rely on the `docs/new-schema.sql` and `docs/seed.sql` files.
* In particular, the `docs/seed.sql` file contains essential data for the `Projects` table, utilized by the tests.
  * If tests encounter failures due to changes in the data, ensure that the `PHONE_ID_#` in test file `tests/integration/UploadSlideBackTranslationIntegrationTest.php` corresponds to the `androidId` column value in the `Projects` table.
* The current `androidId` values used in the test file are:
  * `lmnopq`
  * `ghijkl`
  * `rstuvw`


#### Test Story Template:
Navigate to the test story template within the project structure:
* Located within the `tests` directory, the test story template resides in the `tests/data/templates` directory.
* The structure of the story template is as follows:
  * `story_1` serves as the story template in the test file. Ensure its alignment with the `STORY_TEMPLATE` constant declared in the test file.
  * Within the `story_1/project` directory, find a file named `story.json` containing information about the story slide. Note: Do not alter the filename.
  * The `slideType` set to `COPYRIGHT` is not processed by `UploadSlideBacktranslation.php`.
* Language-specific data for the template can be found in the `tests/data/templates/es` directory, maintaining the same overall structure.


### Running Tests:
* Ensure that all dependencies are installed by running:
  ```php
  php composer.phar install
  ```
* Execute tests using the following command:
  ```php
  ./vendor/bin/phpunit --testdox --no-coverage
  ```


