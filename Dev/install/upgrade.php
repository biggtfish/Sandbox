<?php
/* Sandbox v0.5-1.0b http://sandbox.kiasyn.com
 * Copyright (c) 2006-2007 Sam O'Connor (Kiasyn)
 *
 * Additions to Sandbox after 1.0:
 * Copyright (c) 2007-2015
 * Roger Libiez [Samson] http://www.iguanadons.net
 *
 * Sandbox installer module. Based on QSF Portal installer module.
 * QSF Portal Copyright (c)2006-2007 The QSF Portal Team
 *
 * This software is provided 'as-is', without any express or implied warranty.
 * In no event will the authors be held liable for any damages arising from the
 * use of this software.
 *
 * Permission is granted to anyone to use this software for any purpose,
 * including commercial applications, and to alter it and redistribute it freely,
 * subject to the following restrictions:
 *
 * 1. The origin of this software must not be misrepresented; you must not claim
 * that you wrote the original software. If you use this software in a product,
 * an acknowledgment in the product documentation is required.
 *
 * 2. Altered source versions must be plainly marked as such, and must not be
 * misrepresented as being the original software.
 *
 * 3. This notice may not be removed or altered from any source distribution.
 *
 * 4. You must make an effort to notify the author (Sam O'Connor) at the email
 * address sandbox@kiasyn.com if you plan on publicly distributing a derivative
 * of this software, whether by email, download or a form of disk/disc.
 *
 * Notifying Roger Libiez is not required but would still be appreciated :)
 */

if ( !defined('SANDBOX_INSTALLER') )
	die;

class upgrade extends module
{
	function upgrade_site( $step )
	{
		switch($step)
		{
			default:
			echo "<form action='{$this->self}?mode=upgrade&amp;step=2' method='post'>
			 <div class='article'>
			  <div class='title'>Upgrade Sandbox</div>
			  <div class='subtitle'>Directory Permissions</div>";

			check_writeable_files();

			$dbt = 'db_' . $this->settings['db_type'];
			$db = new $dbt( $this->settings['db_name'], $this->settings['db_user'], $this->settings['db_pass'], $this->settings['db_host'], $this->settings['db_pre'] );

			if ( !$db->db )
			{
				echo '<br /><br />A connection to the database could not be established. Please check your settings.php file to be sure it has the correct information.';
				break;
			}
			$this->db = $db;

			// Need to do this before anything else.
			$coms = $this->db->quick_query( "SELECT COUNT(*) count FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = '{$this->settings['db_name']}' AND table_name = '%psettings'" );

			if( $coms['count'] < 3 ) {
				$this->db->dbquery( "ALTER TABLE %psettings ADD settings_version smallint(2) NOT NULL default '1' AFTER settings_id" );
			}

			$this->settings = $this->load_settings($this->settings);

			$v_message = 'To determine what version you are running, check the bottom of your AdminCP page. Or check the CHANGELOG file and look for the latest revision mentioned there.';
			if( isset($this->settings['app_version']) )
				$v_message = 'The upgrade script has determined you are currently using ' . $this->settings['app_version'];

			echo "<br /><br /><strong>{$v_message}</strong>";

			if( $this->settings['app_version'] == $this->version ) {
				echo "<br /><br /><strong>The detected version of Sandbox is the same as the version you are trying to upgrade to. The upgrade cannot be processed.</strong>";
			} else {
				echo "	<div class='title' style='text-align:center'>Upgrade from what version?</div>

					<span class='field'><input type='radio' name='from' value='2.2' id='220' /></span>
					<span class='form'><label for='220'>Sandbox 2.2</label></span>
					<p class='line'></p>

					<span class='field'><input type='radio' name='from' value='2.1' id='210' /></span>
					<span class='form'><label for='210'>Sandbox 2.1</label></span>
					<p class='line'></p>

					<span class='field'><input type='radio' name='from' value='2.0.7' id='207' /></span>
					<span class='form'><label for='207'>Sandbox 2.0 thru 2.0.7</label></span>
					<p class='line'></p>

					<span class='field'><input type='radio' name='from' value='1.8' id='180' /></span>
					<span class='form'><label for='180'>Sandbox 1.8</label></span>
					<p class='line'></p>

					<div style='text-align:center'>
					 <input type='submit' value='Continue' />
					 <input type='hidden' name='mode' value='upgrade' />
					 <input type='hidden' name='step' value='2' />
					</div>";
			}
			echo "    </div>
			    </form>\n";
			break;

			case 2:
			 	echo "<div class='article'>
			 		<div class='title'>Upgrade Sandbox</div>";
				$dbt = 'db_' . $this->settings['db_type'];
				$db = new $dbt( $this->settings['db_name'], $this->settings['db_user'], $this->settings['db_pass'], $this->settings['db_host'], $this->settings['db_pre'] );

				if ( !$db->db )
				{
					echo '<br />A connection to the database could not be established. Please check your settings.php file to be sure it has the correct information.';
					break;
				}
				$this->db = $db;
				$this->settings = $this->load_settings($this->settings);

				// Missing breaks are deliberate. Upgrades from older versions need to step through all of this.
				switch($this->post['from'])
				{
					case '1.8': // 1.8 to 2.0
						$this->settings['sidebar_images'] = true;

						$queries[] = "CREATE TABLE %pactive (
						 	active_action varchar(50) NOT NULL,
						 	active_time int(10) unsigned NOT NULL,
						 	active_ip varchar(15) NOT NULL,
						 	active_user_agent varchar(100) NOT NULL,
							PRIMARY KEY (active_ip)
					 	) ENGINE=MyISAM DEFAULT CHARSET=utf8";

						$queries[] = "CREATE TABLE %prandom_quotes (
						 	quote_id int(10) unsigned NOT NULL AUTO_INCREMENT,
						 	quote_text text,
						 	PRIMARY KEY (quote_id)
					 	) ENGINE=MyISAM DEFAULT CHARSET=utf8";

						$queries[] = "CREATE TABLE %pspam (
						 	spam_id int(12) unsigned NOT NULL AUTO_INCREMENT,
							spam_post int(12) unsigned NOT NULL,
							spam_author varchar(30) NOT NULL,
						 	spam_ip varchar(15) NOT NULL,
						 	spam_url varchar(100) DEFAULT NULL,
						 	spam_message text NOT NULL,
							spam_date int(10) unsigned NOT NULL,
							spam_server text NOT NULL,
						 	spam_type int(10) unsigned NOT NULL DEFAULT '0',
							spam_user int(10) unsigned NOT NULL DEFAULT '1',
						 	PRIMARY KEY (spam_id),
						 	KEY spam_post (spam_post)
						) ENGINE=MyISAM DEFAULT CHARSET=utf8";

						$queries[] = "DROP TABLE IF EXISTS %ptemplates";

						$queries[] = "ALTER TABLE %pblogcats CHANGE cat_name cat_name varchar(50) NOT NULL DEFAULT ''";
						$queries[] = "ALTER TABLE %pblogposts ADD post_image varchar(50) NOT NULL DEFAULT '' AFTER post_comment_count";
						$queries[] = "ALTER TABLE %pblogposts CHANGE post_description post_summary varchar(255) DEFAULT NULL";
						$queries[] = 'ALTER TABLE %pblogcomments CHANGE comment_post comment_post int(12) unsigned NOT NULL';
						$queries[] = "ALTER TABLE %pblogcomments ADD comment_editdate int(10) unsigned NOT NULL DEFAULT '0' AFTER comment_date";
						$queries[] = "ALTER TABLE %pblogcomments ADD comment_user int(10) unsigned NOT NULL DEFAULT '1' AFTER comment_post";
						$queries[] = "ALTER TABLE %pblogcomments ADD comment_type tinyint(2) unsigned NOT NULL DEFAULT '0' AFTER comment_editdate";
						$queries[] = 'ALTER TABLE %pblogcomments ADD comment_referrer tinytext AFTER comment_type';
						$queries[] = 'ALTER TABLE %pblogcomments ADD comment_agent tinytext AFTER comment_referrer';
						$queries[] = 'ALTER TABLE %pblogcomments DROP COLUMN comment_url';
						$queries[] = "ALTER TABLE %pfilefolders CHANGE folder_parent folder_parent int(11) NOT NULL DEFAULT '0'";
						$queries[] = "ALTER TABLE %pfilefolders CHANGE folder_protected folder_hidden tinyint(1) NOT NULL DEFAULT '0'";
						$queries[] = "ALTER TABLE %pfilefolders CHANGE folder_password folder_password varchar(33) NOT NULL DEFAULT ''";
						$queries[] = "ALTER TABLE %pfilefolders ADD folder_summary varchar(255) DEFAULT '' AFTER folder_name";
						$queries[] = 'ALTER TABLE %pfilelist CHANGE file_filename file_filename varchar(100) NOT NULL';
						$queries[] = 'ALTER TABLE %pfilelist ADD file_shortdesc text AFTER file_description';
						$queries[] = "ALTER TABLE %pfilelist ADD file_img_ext varchar(5) DEFAULT '' AFTER file_shortdesc";
						$queries[] = "ALTER TABLE %pfilelist CHANGE file_md5name file_md5name varchar(33) NOT NULL DEFAULT ''";
						$queries[] = "ALTER TABLE %pfilelist CHANGE file_type file_type varchar(4) NOT NULL DEFAULT ''";
						$queries[] = "ALTER TABLE %pfilelist ADD file_comment_count int(10) unsigned NOT NULL DEFAULT '0' AFTER file_downloaded";
						$queries[] = "ALTER TABLE %pfilelist ADD file_flags int(10) unsigned NOT NULL DEFAULT '0' AFTER file_comment_count";
						$queries[] = "ALTER TABLE %pfilelist CHANGE file_adddate file_date int(10) unsigned NOT NULL DEFAULT '0'";
						$queries[] = 'ALTER TABLE %ppages CHANGE page_id page_id int(10) unsigned NOT NULL AUTO_INCREMENT';
						$queries[] = "ALTER TABLE %pphotofolders CHANGE folder_parent folder_parent int(11) NOT NULL DEFAULT '0'";
						$queries[] = "ALTER TABLE %pphotofolders CHANGE folder_protected folder_hidden tinyint(1) NOT NULL DEFAULT '0'";
						$queries[] = "ALTER TABLE %pphotofolders CHANGE folder_password folder_password varchar(33) NOT NULL DEFAULT ''";
						$queries[] = "ALTER TABLE %pphotofolders ADD folder_summary varchar(255) DEFAULT '' AFTER folder_name";
						$queries[] = "ALTER TABLE %pphotogallery CHANGE photo_caption photo_caption varchar(50) NOT NULL DEFAULT ''";
						$queries[] = "ALTER TABLE %pphotogallery CHANGE photo_md5name photo_md5name varchar(33) NOT NULL DEFAULT ''";
						$queries[] = "ALTER TABLE %pphotogallery ADD photo_flags int(10) unsigned NOT NULL DEFAULT '0' AFTER photo_adddate";
						$queries[] = "ALTER TABLE %pphotogallery ADD photo_comment_count int(10) unsigned NOT NULL DEFAULT '0' AFTER photo_flags";
						$queries[] = "ALTER TABLE %pphotogallery ADD photo_summary varchar(255) DEFAULT '' AFTER photo_caption";
						$queries[] = "ALTER TABLE %pphotogallery CHANGE photo_adddate photo_date int(10) unsigned NOT NULL DEFAULT '0'";
						$queries[] = "ALTER TABLE %pusers CHANGE user_password user_password varchar(64) NOT NULL DEFAULT ''";
						$queries[] = "ALTER TABLE %pusers ADD user_email varchar(100) NOT NULL DEFAULT '' AFTER user_password";
						$queries[] = "ALTER TABLE %pusers ADD user_url varchar(100) DEFAULT '' AFTER user_email";
						$queries[] = "ALTER TABLE %pusers ADD user_stylesheet varchar(100) DEFAULT '' AFTER user_url";
						$queries[] = "ALTER TABLE %pusers ADD user_level smallint(2) unsigned NOT NULL DEFAULT '1' AFTER user_stylesheet";
						$queries[] = "ALTER TABLE %pusers ADD user_perms smallint(4) unsigned NOT NULL DEFAULT '0' AFTER user_level";
						$queries[] = "ALTER TABLE %pusers ADD user_joined int(10) unsigned DEFAULT '0' AFTER user_perms";
						$queries[] = "ALTER TABLE %pusers ADD user_ip varchar(15) NOT NULL DEFAULT '127.0.0.1' AFTER user_joined";
						$queries[] = 'ALTER TABLE %pusers DROP COLUMN user_isowner';

						$this->settings['footer_text'] = '';
						$this->settings['copyright_terms'] = '';
						$this->settings['sidebar_comments_count'] = 5;
						$this->settings['sidebar_images_count'] = 5;

					case '2.0.7': // 2.0-2.0.7 to 2.1
						$queries[] = 'ALTER TABLE %pactive CHANGE active_ip active_ip varchar(40) NOT NULL';
						$queries[] = 'ALTER TABLE %pblogcomments CHANGE comment_ip comment_ip varchar(40) NOT NULL';
						$queries[] = 'ALTER TABLE %pblogcomments CHANGE comment_message comment_message mediumtext NOT NULL';
						$queries[] = 'ALTER TABLE %pblogposts CHANGE post_text post_text mediumtext NOT NULL';
						$queries[] = 'ALTER TABLE %pfilelist ADD file_version varchar(10) AFTER file_img_ext';
						$queries[] = 'ALTER TABLE %ppages CHANGE page_content page_content mediumtext NOT NULL';
						$queries[] = 'ALTER TABLE %pspam CHANGE spam_message spam_message mediumtext NOT NULL';
						$queries[] = 'ALTER TABLE %pspam CHANGE spam_ip spam_ip varchar(40) NOT NULL';
						$queries[] = 'ALTER TABLE %pusers CHANGE user_ip user_ip varchar(40) NOT NULL';

						$this->settings['blog_commentsperpage'] = 50;
						$this->settings['download_size'] = 0;

					case '2.1': // 2.1 to 2.2
						$this->settings['anonymous_comments'] = true; // They were always on before, this continues behavior for existing sites
						$this->settings['global_comments'] = true;

						$queries[] = "CREATE TABLE %pemoticons (
						  emote_id int(10) unsigned NOT NULL auto_increment,
						  emote_string varchar(15) NOT NULL default '',
						  emote_image varchar(255) NOT NULL default '',
						  emote_clickable tinyint(1) unsigned NOT NULL default '1',
						  PRIMARY KEY  (emote_id)
						) ENGINE=MyISAM DEFAULT CHARSET=utf8";

						$queries[] = "INSERT INTO %pemoticons (emote_string, emote_image, emote_clickable) VALUES (':alien:', 'alien.gif', 1 )";
						$queries[] = "INSERT INTO %pemoticons (emote_string, emote_image, emote_clickable) VALUES (':biggrin:', 'biggrin.gif', 1 )";
						$queries[] = "INSERT INTO %pemoticons (emote_string, emote_image, emote_clickable) VALUES (':blues:', 'blues.gif', 1 )";
						$queries[] = "INSERT INTO %pemoticons (emote_string, emote_image, emote_clickable) VALUES (':cool:', 'cool.gif', 1 )";
						$queries[] = "INSERT INTO %pemoticons (emote_string, emote_image, emote_clickable) VALUES (':cry:', 'cry.gif', 1 )";
						$queries[] = "INSERT INTO %pemoticons (emote_string, emote_image, emote_clickable) VALUES (':cyclops:', 'cyclops.gif', 1 )";
						$queries[] = "INSERT INTO %pemoticons (emote_string, emote_image, emote_clickable) VALUES (':devil:', 'devil.gif', 1 )";
						$queries[] = "INSERT INTO %pemoticons (emote_string, emote_image, emote_clickable) VALUES (':evil:', 'evil.gif', 1 )";
						$queries[] = "INSERT INTO %pemoticons (emote_string, emote_image, emote_clickable) VALUES (':ghostface:', 'ghostface.gif', 1 )";
						$queries[] = "INSERT INTO %pemoticons (emote_string, emote_image, emote_clickable) VALUES (':grinning:', 'grinning.gif', 1 )";
						$queries[] = "INSERT INTO %pemoticons (emote_string, emote_image, emote_clickable) VALUES (':lol:', 'lol.gif', 1 )";
						$queries[] = "INSERT INTO %pemoticons (emote_string, emote_image, emote_clickable) VALUES (':mad:', 'mad.gif', 1 )";
						$queries[] = "INSERT INTO %pemoticons (emote_string, emote_image, emote_clickable) VALUES (':redface:', 'redface.gif', 1 )";
						$queries[] = "INSERT INTO %pemoticons (emote_string, emote_image, emote_clickable) VALUES (':robot:', 'robot.gif', 1 )";
						$queries[] = "INSERT INTO %pemoticons (emote_string, emote_image, emote_clickable) VALUES (':rolleyes:', 'rolleyes.gif', 1 )";
						$queries[] = "INSERT INTO %pemoticons (emote_string, emote_image, emote_clickable) VALUES (':sad:', 'sad.gif', 1 )";
						$queries[] = "INSERT INTO %pemoticons (emote_string, emote_image, emote_clickable) VALUES (':smile:', 'smile.gif', 1 )";
						$queries[] = "INSERT INTO %pemoticons (emote_string, emote_image, emote_clickable) VALUES (':stare:', 'stare.gif', 1 )";
						$queries[] = "INSERT INTO %pemoticons (emote_string, emote_image, emote_clickable) VALUES (':surprised:', 'surprised.gif', 1 )";
						$queries[] = "INSERT INTO %pemoticons (emote_string, emote_image, emote_clickable) VALUES (':thinking:', 'thinking.gif', 1 )";
						$queries[] = "INSERT INTO %pemoticons (emote_string, emote_image, emote_clickable) VALUES (':tongue:', 'tongue.gif', 1 )";
						$queries[] = "INSERT INTO %pemoticons (emote_string, emote_image, emote_clickable) VALUES (':unclesam:', 'unclesam.gif', 1 )";
						$queries[] = "INSERT INTO %pemoticons (emote_string, emote_image, emote_clickable) VALUES (':wink:', 'wink.gif', 1 )";
						$queries[] = "INSERT INTO %pemoticons (emote_string, emote_image, emote_clickable) VALUES (':huh:', 'huh.gif', 1 )";
						$queries[] = "INSERT INTO %pemoticons (emote_string, emote_image, emote_clickable) VALUES (':blink:', 'blink.gif', 1 )";
						$queries[] = "INSERT INTO %pemoticons (emote_string, emote_image, emote_clickable) VALUES (':facepalm:', 'facepalm.gif', 1 )";
						$queries[] = "INSERT INTO %pemoticons (emote_string, emote_image, emote_clickable) VALUES (':whistle:', 'whistle.gif', 1 )";
						$queries[] = "INSERT INTO %pemoticons (emote_string, emote_image, emote_clickable) VALUES (':sick:', 'sick.gif', 1 )";
						$queries[] = "INSERT INTO %pemoticons (emote_string, emote_image, emote_clickable) VALUES (':headbang:', 'headbang.gif', 1 )";
						$queries[] = "INSERT INTO %pemoticons (emote_string, emote_image, emote_clickable) VALUES (':innocent:', 'innocent.png', 1 )";
						$queries[] = "INSERT INTO %pemoticons (emote_string, emote_image, emote_clickable) VALUES (':crazy:', 'crazy.gif', 1 )";
						$queries[] = "INSERT INTO %pemoticons (emote_string, emote_image, emote_clickable) VALUES (':rofl:', 'rofl.gif', 1 )";
						$queries[] = "INSERT INTO %pemoticons (emote_string, emote_image, emote_clickable) VALUES (':lmao:', 'lmao.gif', 1 )";
						$queries[] = "INSERT INTO %pemoticons (emote_string, emote_image, emote_clickable) VALUES (':shrug:', 'shrug.gif', 1 )";
						$queries[] = "INSERT INTO %pemoticons (emote_string, emote_image, emote_clickable) VALUES (':ninja:', 'ninja.gif', 1 )";
						$queries[] = "INSERT INTO %pemoticons (emote_string, emote_image, emote_clickable) VALUES (':nuke:', 'nuke.gif', 1 )";
						$queries[] = "INSERT INTO %pemoticons (emote_string, emote_image, emote_clickable) VALUES (':wub:', 'wub.png', 1 )";
						$queries[] = "INSERT INTO %pemoticons (emote_string, emote_image, emote_clickable) VALUES (':imp:', 'imp.png', 1 )";
						$queries[] = "INSERT INTO %pemoticons (emote_string, emote_image, emote_clickable) VALUES (':banana:', 'dancingbanana.gif', 1 )";
						$queries[] = "INSERT INTO %pemoticons (emote_string, emote_image, emote_clickable) VALUES (':cricket:', 'cricket.png', 1 )";
						$queries[] = "INSERT INTO %pemoticons (emote_string, emote_image, emote_clickable) VALUES (':(', 'sad.gif', 0 )";
						$queries[] = "INSERT INTO %pemoticons (emote_string, emote_image, emote_clickable) VALUES (':P', 'tongue.gif', 0 )";
						$queries[] = "INSERT INTO %pemoticons (emote_string, emote_image, emote_clickable) VALUES (';)', 'wink.gif', 0 )";
						$queries[] = "INSERT INTO %pemoticons (emote_string, emote_image, emote_clickable) VALUES (':)', 'smile.gif', 0 )";

					case '2.2': // 2.2 to 2.3
						$this->settings['site_tagline'] = '';

					default:
						break;
				}

				execute_queries($queries, $this->db);

				$this->settings['app_version'] = $this->version;
				$this->save_settings();

				echo "<div class='title'>Upgrade Successful</div>
					You can <a href=\"../index.php\">return to your site</a> now.<br /><br />
				        <span style='color:red'>Please DELETE THE INSTALL DIRECTORY NOW for security purposes!!</span>
				</div>";
				break;
		}
	}
}
?>