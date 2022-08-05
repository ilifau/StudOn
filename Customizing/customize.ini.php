; <?php exit; ?>

; fau: customSettings - local customizations of the ILIAS user interface
;
; This file is read by class.ilCust.php
; Settings can be used with ilCust::get("setting")
;
; Settings are looked up in the following ini files:
;
; 1. [customize] section in data/<client>/client.ini.php
; 2. [default]   section in Customizing/customize.ini.php
;
; Each setting should have at least a definition
; in the default section of customize.ini.php (last lookup).
; The default setting should correspond to a non-customized ILIAS.
;
; Settings should have positive naming similar to:
; <module>_<show|enable|with>_<element> = "0|1"
;
; Dynamic settings:
; repository_is_visible				;true if repository is visible
; administration_is_visible			;true if administration is visible


[default]
; settings to be changed per client in client.ini.php
;
cat_enable_univis_import = "0"          ;enable univis import for categories
crs_enable_univis_import = "0"          ;enable univis import for courses

crs_enable_reg_codes = "1"				;enable the use of registration codes
grp_enable_reg_codes = "1"				;enable the use of registration codes

fair_admin_role_id = ""                 ;Role Id of users who are allowed to deactivate the fair time for registrations
help_show_ids = "0"						;show the screen and tooltip IDs

fau_default_owner_login = "root"        ;default login for the owner of auto-created objects
fau_course_dtpl_id = ""                 ;id of the didactic template for auto-generated courses
fau_group_dtpl_id = ""                  ;id of the didactic template for auto-generated groups
fau_fallback_parent_cat_id = ""         ;id of the fallback parent category for the creation categories of courses
fau_move_parent_cat_ids = ""            ;ids of parents (e.g. faculties) of course categories from which courses can be moved (comma separated)
fau_exclude_create_org_ids = ""         ;ids of org units to be excluded from the creation of courses
fau_author_role_template_id = ""        ;id of the author role template for categories
fau_manager_role_template_id = ""       ;id of the manager role template for categories

ilias_guest_role_id = "5"				;Role Id of Guests
ilias_copy_by_soap = "1"				;fau: copyBySoap - setting to use SOAP client to copy container objects
ilias_copy_always_mail ="0"             ;fau: copyBySoap - always send an e-mail confirmation
ilias_log_request_ips = ""              ;fau: requestLog - comma separates list of ip adresses for which a request log should be written
ilias_show_roles_info = "0"             ;Show roles with permissions on info screen
ilias_trace_redirects = "0"             ;show redirect info instead of redirecting

local_auth_external = "0"               ;allow local login with external account
local_auth_matriculation = "0"          ;allow local login with matriculation number
local_auth_remote = "0"                 ;check password of an account with the same login in a remote platform
local_auth_idm = "0"                    ;check password of the ext_account in the idm database

lp_refreshes_limit = "0"				;fau: lpRefreshesLimit - limit allowed status refreshes when learning progress is shown

mail_by_soap = "1"						;fau: mailBySoap - switch sending of external mails in the background

mycampus_enabled = "0"                  ;enable my campus registration
mycampus_reg_url = ""                   ;my campus registration url (univis id is included by %s)
mycampus_soap_url = ""                  ;url of the my campus SOAP service
mycampus_soap_client = ""               ;client for calling the my campus SOAP service
mycampus_soap_user = ""                 ;user for calling the my campus SOAP service
mycampus_soap_password = ""             ;password for calling the my campus SOAP service
mycampus_sync_waitinglist = "0"         ;add users of waiting list in mein campus to waiting list in studon
                                        ;(use only once before disconnecting)

mycampus_sync_enabled = "0"				;enable synchronisation by chron-job
mycampus_sync_logfile = ""				;logfile to be written for mycampus synchronisation
mycampus_sync_mail_interval = ""		;"year", "month", "week", "day", "hour", "minute", "all"
mycampus_sync_mail_verbose = "0"		;list all courses (not only those with errors or actions)

remote_soap_server = ""                 ;soap url of a remote installation
remote_soap_client_id = ""              ;soap client id of the remote server
remote_soap_user = ""                   ;soap user of a remote installation
remote_soap_password = ""               ;soap password of a remote installation

reg_code_length = "10"                  ;length of generated registration code (max. 10)

search_enable_autocomplete = "1"        ;enable auto-complete in object search field

shib_allow_create = "1"                 ;allow the creation of user accounts by shibboleth
shib_create_limited = ""				;time limit for shib created accounts, e.g. 2015-10-01
shib_log_accounts = ""                  ;comma separated list of accounts for which a shibboleth login should be logged
shib_devmode_identity = ""              ;force an identity at shibboleth authentication in devmode (to fetch idm data)
shib_devmode_login = ""                 ;force a login at shibboleth authentication in devmode (to get a studon user)

studydata_check_ref_ids = "";           ;list of ref_ids for which studydata are checked (comma-separated without spaces)

tst_notify_remote = "0"                 ;send mails to users at remote installation (for exam)
tst_export_mycampus = "0"               ;enable the export of test results for my campus
tst_prevent_image_drag = "0"			;prevent the dragging of images an links to texts fields in tests
tst_prevent_image_validate = "0"        ;fau: preventQtiImageValidate - prevent validation of images at test/pool import

univis_server = ""                      ;only the server name, e.g. univis.uni-erlangen.de
univis_port = ""                        ;port sor testing socket connection in univis2mysql
univis_prg_url = ""                     ;url if the prg interface (ending with /?)
univis_tempdir = ""                    ;temporary directory for storing fetched xml files
univis_semester = ""                    ;set a specific semester for the import (if empty: current and following)
univis_noimports = ""                   ;don't show imported lectures (1)

unzip_keep_min_kyrillic_percent = "0"   ;min percent of kyrillic characters (if any) to keep the default encoding

videoportal_token = ""                  ;token for the videoportal to call the studon service
            
webdav_show_warnings = "1"				;show warnings about locking and invisible names for webdav
