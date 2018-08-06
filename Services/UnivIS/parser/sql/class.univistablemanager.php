<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2008 Starbugs (univis2typo3@googlegroups.com)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * fim: [univis] new class for maintaining univis data tables schema
 *
 * Taken from the univis2typo3 project and adapted for ILIAS
 *
 * Use INDEX for separate fields instead of PRIMARY KEY:
 * - MySQL keys can be maximum 1000 bytes
 * - ILIAS tables are utf8
 * - utf8 fields have 3 bytes per character
 *
 * Use DELETE by primary key fields and INSERT instead of REPLACE!
 *
 * @author	Starbugs <univis2typo3@googlegroups.com>
 * @modifier Fred neumann <fred.neumann@fim.uni-erlangen.de>
 */
class univistablemanager
{ 
    static function create_univis_tables()
    {
		$sql_query = array();
		
		// group_concat function not used
		// $sql_query[]='SET GLOBAL group_concat_max_len = 16384';

		$sql_query[]='CREATE TABLE IF NOT EXISTS univis_person (
			session_id VARCHAR(80),
			`department_id` INTEGER,
			`key` VARCHAR(255),
			`type` VARCHAR(50),
			`group` VARCHAR(50),
			atitle VARCHAR(50),
			firstname VARCHAR(100),
			`from` VARCHAR(255),
			`id` VARCHAR(255),
			lastname VARCHAR(100),
			lehr VARCHAR(255),
			lehraufg VARCHAR(255),
			lehrtyp VARCHAR(255),
			pgroup VARCHAR(255),
			shortname VARCHAR(255),
			`title` VARCHAR(255),
			`univis_key` VARCHAR(255),
			`until` VARCHAR(255),
			visible VARCHAR(255),
			work VARCHAR(255),
			zweitmgl VARCHAR(255),
			alumni VARCHAR(255),
			chef VARCHAR(255),
			founder VARCHAR(255),
			name VARCHAR(255),
			orgname VARCHAR(255),
			current ENUM ( "0", "1" ) DEFAULT "0",
			gender SET ( "female", "male" ),
			INDEX (session_id),
			INDEX (department_id),
			INDEX (`key`)
			)';
			//PRIMARY KEY(`key`)

		// projects := ( HUMAINE, IMPRS, PF-STAR, SFB-539, SFB-603, SVcheck, SmartWeb )* -> univis_person_research,
		// type := acad, admin, azubi, externalPhD, guest, info, secretary, trainee, webmaster

		$sql_query[]='CREATE TABLE IF NOT EXISTS univis_person_location (
			session_id VARCHAR(80),
			`department_id` INTEGER,
			person_key VARCHAR(255) REFERENCES univis_person,
			orderindex INTEGER,
			email VARCHAR(255),
			fax VARCHAR(255),
			mobile VARCHAR(255),
			office VARCHAR(255),
			ort VARCHAR(255),
			street VARCHAR(255),
			tel VARCHAR(255),
			url VARCHAR(255),
			INDEX (session_id),
			INDEX (department_id),
			INDEX (person_key)
			)';
			// 	PRIMARY KEY (person_key, ort, street, office)


		$sql_query[]='CREATE TABLE IF NOT EXISTS univis_person_officehour (
			session_id VARCHAR(80),
			`department_id` INTEGER,
			person_key VARCHAR(255) REFERENCES univis_person,
			orderindex INTEGER,
			endtime VARCHAR(255),
			office VARCHAR(255),
			`repeat` VARCHAR(255),
			starttime VARCHAR(255),
			`comment` VARCHAR(255),
			INDEX (session_id),
			INDEX (department_id),
            INDEX (person_key)
			)';
			//PRIMARY KEY (person_key, starttime, repeat)

		$sql_query[]='CREATE TABLE IF NOT EXISTS univis_research (
			session_id VARCHAR(80),
			`department_id` INTEGER,
			`key` VARCHAR(255),
			contact VARCHAR(255) REFERENCES univis_person,
			description TEXT,
			description_en TEXT,
			enddate VARCHAR(255),
			orgname VARCHAR(255),
			keywords VARCHAR(255),
			keywords_en VARCHAR(255),
			startdate VARCHAR(255),
			title VARCHAR(255),
			title_en VARCHAR(255),
			url VARCHAR(255),
			url_en VARCHAR(255),
			INDEX (session_id),
			INDEX (department_id),
			INDEX (`key`)
			)';
			//PRIMARY KEY (`key`)

		// TODO
		//    promoters , -> univis_research_promotions ?
		//    coworkers , -> univis_research_coworkers
		//    directors , -> univis_research_directors
		//    externals , -> univis_research_externals
		//    publics , -> univis_research_publics

		$sql_query[]='CREATE TABLE IF NOT EXISTS univis_research_promoters (
			session_id VARCHAR(80),
			`department_id` INTEGER,
			project_key VARCHAR(255) references univis_research,
			name VARCHAR(255),
			url VARCHAR(255),
			INDEX (session_id),
			INDEX (department_id),
			INDEX (project_key),
			INDEX (name)
			)';
			//PRIMARY KEY (project_key, name)

		$sql_query[]='CREATE TABLE IF NOT EXISTS univis_research_externals (
			session_id VARCHAR(80),
			`department_id` INTEGER,
			project_key VARCHAR(255) REFERENCES univis_research,
			name VARCHAR(255),
			url VARCHAR(255),
			INDEX (session_id),
			INDEX (department_id),
			INDEX (project_key),
			INDEX (name)
			)';
			//PRIMARY KEY (project_key, name)

		$sql_query[]='CREATE TABLE IF NOT EXISTS univis_research_publics (
			session_id VARCHAR(80),
			`department_id` INTEGER,
			project_key VARCHAR(255) REFERENCES univis_research,
			`public` VARCHAR(255),
			INDEX (session_id),
			INDEX (department_id),
			INDEX (project_key),
			INDEX (`public`)
			)';
			//PRIMARY KEY (project_key, `public`)

		$sql_query[]='CREATE TABLE IF NOT EXISTS univis_research_directors (
			session_id VARCHAR(80),
			`department_id` INTEGER,
			project_key VARCHAR(255) REFERENCES univis_research,
			director VARCHAR(255) REFERENCES univis_person,
			INDEX (session_id),
			INDEX (department_id),
			INDEX (project_key),
			INDEX (director)
			)';
			//PRIMARY KEY (project_key, director)

		$sql_query[]='CREATE TABLE IF NOT EXISTS univis_research_coworkers (
			session_id VARCHAR(80),
			`department_id` INTEGER,
			project_key VARCHAR(255) REFERENCES univis_research,
			coworker VARCHAR(255) REFERENCES univis_person,
			INDEX (session_id),
			INDEX (department_id),
			INDEX (project_key),
			INDEX (coworker)
			)';
			//PRIMARY KEY (project_key, coworker)

		// $sql_query[]='CREATE TABLE IF NOT EXISTS univis_person_research (
		//     person_key VARCHAR(255) REFERENCES univis_person,
		//     project_key VARCHAR(255) REFERENCES univis_research,
		//     PRIMARY KEY (person_key, project_key)
		// )';

		$sql_query[]='CREATE TABLE IF NOT EXISTS univis_title (
			session_id VARCHAR(80),
			`department_id` INTEGER,
			`key` VARCHAR(255),
			parent_title_key VARCHAR(255) REFERENCES univis_title,
			title VARCHAR(255),
			title_en VARCHAR(255),
			`text` TEXT,
			text_en TEXT,
			ordernr VARCHAR(255),
			parent_title VARCHAR(255),
			INDEX (session_id),
			INDEX (department_id),
			INDEX (`key`)
			)';
			//PRIMARY KEY (`key`)

		$sql_query[]='CREATE TABLE IF NOT EXISTS univis_room (
			session_id VARCHAR(80),
			`department_id` INTEGER,
			`key` VARCHAR(255),
			address VARCHAR(255),
			description VARCHAR(255),
			anst VARCHAR(255),
			audio VARCHAR(255),
			beam VARCHAR(255),
			buildno VARCHAR(255),
			dark VARCHAR(255),
			dia VARCHAR(255),
			fest VARCHAR(255),
			`id` VARCHAR(255),
			inet VARCHAR(255),
			lose VARCHAR(255),
			name VARCHAR(255),
			ohead VARCHAR(255),
			roomno VARCHAR(255),
			short VARCHAR(255),
			size VARCHAR(255),
			tafel VARCHAR(255),
			tel VARCHAR(255),
			url VARCHAR(255),
			vcr VARCHAR(255),
			INDEX (session_id),
			INDEX (department_id),
			INDEX (`key`)
			    )';
			//PRIMARY KEY (`key`)

		//    contacts , -> univis_rooms_contacts

		$sql_query[]='CREATE TABLE IF NOT EXISTS univis_room_contacts (
			session_id VARCHAR(80),
			`department_id` INTEGER,
			room_key VARCHAR(255) REFERENCES univis_room,
			contact VARCHAR(255) REFERENCES univis_person,
			INDEX (session_id),
			INDEX (department_id),
			INDEX (contact),
			INDEX (room_key)
			)';
			//PRIMARY KEY (contact, room_key)

		$sql_query[]='CREATE TABLE IF NOT EXISTS univis_lecture (
			session_id VARCHAR(80),
			`department_id` INTEGER,
			`key` VARCHAR(255),
			semester VARCHAR(6),
			classification VARCHAR(255) REFERENCES univis_title,
			parent_lv VARCHAR(255) REFERENCES univis_lecture,
			room VARCHAR(255) REFERENCES univis_room,
			organizational TEXT,
			summary TEXT,
			ects_summary TEXT,
			name VARCHAR(255),
			ects_name VARCHAR(255),
			beginners VARCHAR(255),
			benschein VARCHAR(2),
			`comment` TEXT,
			ects VARCHAR(2),
			ects_cred VARCHAR(10),
			ects_literature VARCHAR(255),
			ects_organizational TEXT,
			evaluation VARCHAR(2),
			fachstud VARCHAR(2),
			fruehstud VARCHAR(2),
			gast VARCHAR(2),
			`id` VARCHAR(255),
			internat VARCHAR(255),
			keywords VARCHAR(255),
			literature TEXT,
			ordernr VARCHAR(255),
			regsystem VARCHAR(50),
			regqueue VARCHAR(50),
			regstart VARCHAR(10),
			regstarttime VARCHAR(10),
			regend VARCHAR(10),
			regendtime VARCHAR(10),
			regwlist VARCHAR(2),
			schein VARCHAR(2),
			short VARCHAR(255),
			startdate VARCHAR(10),
			sws VARCHAR(10),
			time_description VARCHAR(255),
			turnout VARCHAR(10),
			maxturnout VARCHAR(10),
			`type` VARCHAR(10),
			url_description VARCHAR(255),
			orgname VARCHAR(255),
			bonus VARCHAR(10),
			malus VARCHAR(10),
			mag VARCHAR(2),
			dipl VARCHAR(2),
			mast VARCHAR(2),
			bac VARCHAR(2),
			laew VARCHAR(2),
			lafv VARCHAR(2),
			lafn VARCHAR(2),
			lafb VARCHAR(2),
			ladidg VARCHAR(2),
			ladidh VARCHAR(2),
			ladidf VARCHAR(2),
			schluessel VARCHAR(2),
			senior VARCHAR(2),
			women VARCHAR(2),
			allfak VARCHAR(2),
			einf VARCHAR(2),
			schwerp VARCHAR(2),
			medabschn1 VARCHAR(2),
			medabschn2 VARCHAR(2),
			praktjahr VARCHAR(2),
			INDEX (session_id),
			INDEX (department_id),
			INDEX (`key`, `semester`)
			    )';
			//PRIMARY KEY (`key`, `semester`)

		// TODO
		//    courses , -> univis_lecture_courses
		//    dozs , -> univis_lecture_dozs
		//    literature , -> univis_lecture_literature
		//    studs , -> univis_lecture_stud
		//    terms , -> univis_lecture_term
		//    parent-lv , -> parent_lecture ?

		$sql_query[]='CREATE TABLE IF NOT EXISTS univis_lecture_courses (
			session_id VARCHAR(80),
			`department_id` INTEGER,
			lecture_key VARCHAR(255) REFERENCES univis_lecture,
			course VARCHAR(255) REFERENCES univis_lecture,
			semester VARCHAR(6),
			orderindex INTEGER,
			INDEX (session_id),
			INDEX (department_id),
			INDEX (lecture_key, semester),
			INDEX (course)
			)';
			//PRIMARY KEY (lecture_key, semester, course)

		// $sql_query[]='CREATE TABLE IF NOT EXISTS univis_lecture_literature (
		//     lecture_key VARCHAR(255) REFERENCES univis_lecture,
		//     orderindex INTEGER,
		//     name VARCHAR(255),
		//     PRIMARY KEY (lecture_key, orderindex)
		// )';

		$sql_query[]='CREATE TABLE IF NOT EXISTS univis_lecture_stud (
			session_id VARCHAR(80),
			`department_id` INTEGER,
			lecture_key VARCHAR(255) REFERENCES univis_lecture,
			pflicht VARCHAR(50),
			richt VARCHAR(50),
			sem VARCHAR(30),
			semester VARCHAR(6),
			orderindex INTEGER,
			credits VARCHAR(10),
			INDEX (session_id),
			INDEX (department_id),
			INDEX (lecture_key, semester)
			)';
			//PRIMARY KEY (lecture_key, semester, richt, pflicht, sem)

		$sql_query[]='CREATE TABLE IF NOT EXISTS univis_lecture_term (
			session_id VARCHAR(80),
			`department_id` INTEGER,
			lecture_key VARCHAR(255) REFERENCES univis_lecture,
			orderindex INTEGER,
			room VARCHAR(255) REFERENCES univis_room,
			startdate VARCHAR(50),
			starttime VARCHAR(50),
			enddate VARCHAR(50),
			endtime VARCHAR(50),
			exclude VARCHAR(50),
			semester VARCHAR(6),
			`repeat` VARCHAR(10),
			INDEX (session_id),
			INDEX (department_id),
            INDEX (lecture_key, semester)
			)';
			//PRIMARY KEY (lecture_key, semester, `repeat`, starttime)

		$sql_query[]='CREATE TABLE IF NOT EXISTS univis_lecture_dozs (
			session_id VARCHAR(80),
			`department_id` INTEGER,
			lecture_key VARCHAR(255) REFERENCES univis_lecture,
			doz VARCHAR(255) REFERENCES univis_person,
			semester VARCHAR(6),
			orderindex INTEGER,
			INDEX (session_id),
			INDEX (department_id),
            INDEX (lecture_key, semester),
			INDEX (doz)
			)';
			//PRIMARY KEY (lecture_key, doz, semester)

		$sql_query[]='CREATE TABLE IF NOT EXISTS univis_thesis (
			session_id VARCHAR(80),
			`department_id` INTEGER,
			`key` VARCHAR(255),
			title VARCHAR(255),
			notice VARCHAR(255),
			topic VARCHAR(255),
			finishdate VARCHAR(255),
			finishyear VARCHAR(255),
			firstname VARCHAR(255),
			keywords VARCHAR(255),
			lastname VARCHAR(255),
			prerequisit VARCHAR(255),
			public VARCHAR(255),
			registerdate VARCHAR(255),
			reservedate VARCHAR(255),
			short VARCHAR(255),
			status VARCHAR(255),
			`type` VARCHAR(255),
			url VARCHAR(255),
			visible VARCHAR(255),
			INDEX (session_id),
			INDEX (department_id),
			INDEX (`key`)
			)';
			//PRIMARY KEY (`key`)

		// TODO
		//    advisors VARCHAR(255), -> univis_thesis_advisors

		$sql_query[]='CREATE TABLE IF NOT EXISTS univis_thesis_advisors (
			session_id VARCHAR(80),
			`department_id` INTEGER,
			thesis_key VARCHAR(255) REFERENCES univis_theses,
			advisor VARCHAR(255) REFERENCES univis_person,
			INDEX (session_id),
			INDEX (department_id),
			INDEX (thesis_key),
			INDEX (advisor)
			)';
			//PRIMARY KEY (thesis_key, advisor)


		$sql_query[]='CREATE TABLE IF NOT EXISTS univis_pub (
			session_id VARCHAR(80),
			`department_id` INTEGER,
			`key` VARCHAR(255),
			pubtitle VARCHAR(255),
			address VARCHAR(255),
			booktitle VARCHAR(255),
			conf_url VARCHAR(255),
			conference VARCHAR(255),
			edition VARCHAR(255),
			hstype VARCHAR(255),
			hsyear VARCHAR(255),
			`id` VARCHAR(255),
			isbn VARCHAR(255),
			issn VARCHAR(255),
			journal VARCHAR(255),
			keywords VARCHAR(255),
			`number` VARCHAR(255),
			pages VARCHAR(255),
			plocation VARCHAR(255),
			publisher VARCHAR(255),
			puburl VARCHAR(255),
			school VARCHAR(255),
			series VARCHAR(255),
			servolume VARCHAR(255),
			`type` VARCHAR(255),
			volume VARCHAR(255),
			year VARCHAR(255),
			INDEX (session_id),
			INDEX (department_id),
			INDEX (`key`)
			    )';
			//PRIMARY KEY (`key`)

		// TODO
		//    authors , -> univis_pub_authors
		//    editors , -> univis_pub_editors

		$sql_query[]='CREATE TABLE IF NOT EXISTS univis_pub_editors (
			session_id VARCHAR(80),
			`department_id` INTEGER,
			pub_key VARCHAR(255) REFERENCES univis_pub,
			pkey VARCHAR(255) REFERENCES univis_person,
			orderindex INTEGER,
			INDEX (session_id),
			INDEX (department_id),
			UNIQUE INDEX (pub_key, orderindex),
			INDEX (pkey)
			)';
			//PRIMARY KEY (pub_key, pkey)

		$sql_query[]='CREATE TABLE IF NOT EXISTS univis_pub_authors (
			session_id VARCHAR(80),
			`department_id` INTEGER,
			pub_key VARCHAR(255) REFERENCES univis_pub,
			pkey VARCHAR(255) REFERENCES univis_person,
			orderindex INTEGER,
			INDEX (session_id),
			INDEX (department_id),
			UNIQUE INDEX (pub_key, orderindex),
			INDEX  (pkey)
			)';
			//PRIMARY KEY (pub_key, pkey)

		$sql_query[]='CREATE TABLE IF NOT EXISTS univis_job (
			session_id VARCHAR(80),
			`department_id` INTEGER,
			`parent_key` VARCHAR(255),
			`description` VARCHAR(255),
			`description_en` VARCHAR(255),
			`flags` VARCHAR(255),
			INDEX (session_id),
			INDEX (department_id),
			INDEX (`description`)
			)';
            //PRIMARY KEY(`description`)

		$sql_query[]='CREATE TABLE IF NOT EXISTS univis_person_jobs (
			session_id VARCHAR(80),
			`department_id` INTEGER,
			per VARCHAR(255) REFERENCES univis_person,
			job_key VARCHAR(255) REFERENCES univis_job,
			INDEX (session_id),
			INDEX (department_id),
			INDEX (per),
			INDEX (job_key)
			)';
			//PRIMARY KEY (per, job_key)

		$sql_query[]='CREATE TABLE IF NOT EXISTS univis_org (
			session_id VARCHAR(80),
			`department_id` INTEGER,
			`key` VARCHAR(255),
			b_lehre VARCHAR(255),
			b_orgdesc VARCHAR(255),
			b_rescoop VARCHAR(255),
			b_resequip VARCHAR(255),
			b_resfoc VARCHAR(255),
			`email` VARCHAR(255),
			fax VARCHAR(255),
			`id` VARCHAR(255),
			`name`VARCHAR(255),
			name_en VARCHAR(255),
			ordernr VARCHAR(255),
			orgdesc VARCHAR(255),
			orgdesc_en VARCHAR(255),
			orgnr VARCHAR(255),
			ort VARCHAR(255),
			pubser VARCHAR(255),
			pubser_en VARCHAR(255),
			resconf VARCHAR(255),
			resconf_en VARCHAR(255),
			rescoop VARCHAR(255),
			rescoop_en VARCHAR(255),
			resequip VARCHAR(255),
			resequip_en VARCHAR(255),
			resfoc VARCHAR(255),
			resfoc_en VARCHAR(255),
			street VARCHAR(255),
			tel VARCHAR(255),
			url VARCHAR(255),
			INDEX (session_id),
			INDEX (department_id),
			INDEX (`key`)
			    )';
			//PRIMARY KEY (`key`)


		global $ilDB;
		foreach ($sql_query as $q)
		{
		    $ilDB->manipulate($q);
		}
    } 

    static function drop_univis_tables()
    {
		$sql_query = array();
		$sql_query[]='DROP TABLE IF EXISTS univis_job';
		$sql_query[]='DROP TABLE IF EXISTS univis_person';
		$sql_query[]='DROP TABLE IF EXISTS univis_person_jobs';
		$sql_query[]='DROP TABLE IF EXISTS univis_person_location';
		$sql_query[]='DROP TABLE IF EXISTS univis_person_officehour';
		$sql_query[]='DROP TABLE IF EXISTS univis_title';
		$sql_query[]='DROP TABLE IF EXISTS univis_research';
		$sql_query[]='DROP TABLE IF EXISTS univis_research_coworkers';
		$sql_query[]='DROP TABLE IF EXISTS univis_research_directors';
		$sql_query[]='DROP TABLE IF EXISTS univis_research_externals';
		$sql_query[]='DROP TABLE IF EXISTS univis_research_promoters';
		$sql_query[]='DROP TABLE IF EXISTS univis_research_publics';
		$sql_query[]='DROP TABLE IF EXISTS univis_room';
		$sql_query[]='DROP TABLE IF EXISTS univis_room_contacts';
		$sql_query[]='DROP TABLE IF EXISTS univis_lecture';
		$sql_query[]='DROP TABLE IF EXISTS univis_lecture_courses';
		$sql_query[]='DROP TABLE IF EXISTS univis_lecture_stud';
		$sql_query[]='DROP TABLE IF EXISTS univis_lecture_term';
		$sql_query[]='DROP TABLE IF EXISTS univis_lecture_dozs';
		$sql_query[]='DROP TABLE IF EXISTS univis_thesis';
		$sql_query[]='DROP TABLE IF EXISTS univis_thesis_advisors';
		$sql_query[]='DROP TABLE IF EXISTS univis_pub';
		$sql_query[]='DROP TABLE IF EXISTS univis_pub_editors';
		$sql_query[]='DROP TABLE IF EXISTS univis_pub_authors';
		$sql_query[]='DROP TABLE IF EXISTS univis_org';

		global $ilDB;
		foreach ($sql_query as $q)
		{
		    $ilDB->manipulate($q);
		}
    } 
}
?>
