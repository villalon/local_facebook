<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.
/**
 * @package    local
 * @subpackage facebook
 * @copyright  2016 Jorge Cabané (jcabane@alumnos.uai.cl)
 * @copyright  2016 Mark Michaelsen (mmichaelsen678@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
//define("AJAX_SCRIPT", true);
//define("NO_DEBUG_DISPLAY", true);

require_once (dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . '/config.php');
require_once ($CFG->dirroot . '/local/facebook/locallib.php');
require_once $CFG->libdir . '/accesslib.php';
global $CFG, $DB, $OUTPUT, $PAGE, $USER;

$action 	  = required_param ('action', PARAM_ALPHAEXT);
$moodleid	  = optional_param ('moodleid', null , PARAM_RAW_TRIMMED);
$courseid 	  = optional_param ('courseid', null , PARAM_RAW_TRIMMED);
$discussionid = optional_param ('discussionid', null, PARAM_RAW_TRIMMED);
$emarkingid   = optional_param ('emarkingid', null, PARAM_RAW_TRIMMED);
//$lastvisit = optional_param ( 'lastvisit', null , PARAM_RAW_TRIMMED );

if ($action == 'get_course_data') {
	global $DB;
	$totaldata = get_course_data($moodleid, $courseid);
	$course = $DB->get_record('course', array('id' => $courseid));
	
	$htmltable = "";
	
	$htmltable .= '<div align="center"><h2>'.$course->fullname.'</h2></div>
				<table class="tablesorter" border="0" width="100%" style="font-size: 13px; margin-left: 9px;">
					<thead>
						<tr>
							<th width="3%" style="border-top-left-radius: 8px;"></th>
							<th width="34%">Título</th>
							<th width="30%">De</th>
							<th width="30%">Fecha</th>
							<th width="3%" style="background-color: transparent"></th>
						</tr>
					</thead>
					<tbody>';
	
	foreach ($totaldata as $module) {
		$date = date ( "d/m/Y H:i", $module ['date'] );
		$component = '';
		$link = '';
		$id = 0;
		$emarkingmodal = '';
		
		$htmltable .= "<tr><td>";
		if ($module ['image'] == FACEBOOK_IMAGE_POST) {
			$htmltable .= '<img src="images/post.png">';
			$component = 'forum';
			$link = "href='#'";
			$id = "discussionid='".$module ['discussion']."'";
		}
	
		else if ($module ['image'] == FACEBOOK_IMAGE_RESOURCE) {
			$htmltable .= '<img src="images/resource.png">';
			$link = "href='".$module['link']."' target='_blank'";
		}
	
		else if ($module ['image'] == FACEBOOK_IMAGE_LINK) {
			$htmltable .= '<img src="images/link.png">';
			$link = "href='".$module['link']."' target='_blank'";
		}
	
		else if ($module ['image'] == FACEBOOK_IMAGE_EMARKING) {
			$htmltable .= '<img src="images/emarking.png">';
			$component = 'emarking';
			$link = "href='#'";
			$id = "emarkingid='".$module['id']."'";
			
			$emarkingmodal .= "<div class='modal fade' id='e".$module['id']."' tabindex='-1' role='dialog' aria-labelledby='modal'>
								<div class='modal-dialog' role='document'>
									<div class='modal-content'>
										<div class='modal-title' align='center'><h4>".$module['title']."</h4></div>
										<div class='modal-body' id='emarking-modal-body'>
											<div class='row'>
												<div class='col-md-4'>
							  						<b>".get_string('name', 'local_facebook')."</b>
								  					<br>".$module['from']."
								  				</div>
								  				<div class='col-md-3'>
								  					<b>".get_string('grade', 'local_facebook')."</b>
								  					<br>";
			
			if($module['status'] >= 20) {
				$emarkingmodal .= $module['grade'];
			} else {
				$emarkingmodal .= "-";
			}
			
			$emarkingmodal .= "</div>
			  				<div class='col-md-3'>
			  					<b>".get_string('status', 'local_facebook')."</b>
			  					<br>";
				
			if($module['status'] >= 20) {
				$emarkingmodal .= get_string('published', 'local_facebook');
			} else if($module['status'] >= 10) {
				$emarkingmodal .= get_string('submitted', 'local_facebook');
			} else {
				$emarkingmodal .= get_string('absent', 'local_facebook');
			}
			
			$emarkingmodal .= "</div>
			  				<div class='col-md-2'>
			  					<br>
			  					<a href='".$module['link']."' target='_blank'>".get_string('viewexam', 'local_facebook')."</a>
			  				</div>
			  			</div>
  					</div>
					<div class='modal-footer'>
						<button type='button' class='btn btn-default' data-dismiss='modal' component='close-modal'>Close</button>
					</div>
				</div>
			</div>
		</div>";
			
			$htmltable .= $emarkingmodal;
		}
	
		else if ($module ['image'] == FACEBOOK_IMAGE_ASSIGN) {
			$htmltable .= '<img src="images/assign.png">';
			$assignid = $module ['id'];
		}
		$htmltable .= "</td><td><a $link component=$component $id>".$module['title']."</a></td>
		<td>". $module['from'] ."</td><td>". $date ."</td></tr>";
	}
	
	$htmltable .= "</tbody></table>";
	
	$jsfunction = "<script>
			$('a').click(function () {
				var aclick = $(this).parent().attr('style');
			
				if ($(this).attr('component') == 'forum') {
					discussionId = $(this).attr('discussionid');
			
					jQuery.ajax({
	 					url : 'https://webcursos-d.uai.cl/local/facebook/app/request.php?action=get_discussion&discussionid=' + discussionId,
	 					async : true,
	 					data : {},
	 					success : function (response) {
							$('#modal-body').empty();
	 						$('#modal-body').append(response);
	 						$('#modal').modal();
 						}
 					});
				}
			
				else if($(this).attr('component') == 'emarking') {
					emarkingId = $(this).attr('emarkingid');
			
					$('#e' + emarkingId).modal();
				}
			
				if(aclick == 'font-weight:bold'){			
					 $(this).parent().parent().children('td').css('font-weight','normal');
					 $(this).parent().parent().children('td').children('center').children('span').css('color','transparent');
					 $(this).parent().parent().children('td').children('button').css('color','#909090');
					 				
					 if(badgecourseid.text() == 1) { 
					 	badgecourseid.remove(); 
					 }
					 else{ 
					 	badgecourseid.text(badgecourseid.text()-1); 
					 }
				}
			});
			</script>";
	
	$htmltable .= $jsfunction;
	
	echo $htmltable;
} 

else if ($action == 'get_discussion') {
	global $DB;
	
	$discussionposts = get_posts_from_discussion($discussionid);
	$htmlmodal = '';
		
	foreach ($discussionposts as $post) {
		$date = $post['date'];
		$htmlmodal .= "<div align='left' style='background-color: #E6E6E6; border-radius: 4px 4px 0 0; padding: 4px; color: #333333;'>
						<img src='images/post.png'>
							<b>&nbsp&nbsp".$post['subject']."<br>&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp".$post['user'].", ".date('l d-F-Y', $date)."</b>
					   </div>
					   <div align='left' style='border-radius: 0 0 4px 4px; word-wrap: break-word;'>".$post['message']."</div><br>";
	}
		
	echo $htmlmodal;
} 

else if ($action == 'get_emarking') {
	global $DB;
	
	$emarkingsql = "SELECT s.id AS id,
			s.grade AS grade,
			s.status AS status,
			cm.id as moduleid,
			CONCAT(u.firstname,' ',u.lastname) AS user
			FROM {emarking_submission} AS s
			INNER JOIN {user} AS u ON (u.id = s.student AND u.id = ?)
			INNER JOIN {course_modules} AS cm ON (cm.instance = s.emarking AND s.emarking = ?)";
	
	$paramsemarking = array(
			$moodleid,
			$emarkingid
	);
	
	$emarkingdata = $DB->get_records_sql($emarkingsql, $paramsemarking);
	$htmlmodal = '';
	
	foreach ($emarkingdata as $emarking) {
		$emarkingurl = new moodle_url('/mod/emarking/view.php', array(
				'id' => $emarking->moduleid
		));
		
		$htmlmodal .= "<div class='row'>
						<div class='col-md-4'>
	  						<b>".get_string('name', 'local_facebook')."</b>
		  					<br>".$emarking->user."
		  				</div>
		  				<div class='col-md-2'>
		  					<b>".get_string('grade', 'local_facebook')."</b>
		  					<br>";
		
	  	if($emarking->status >= 20) {
	  		$htmlmodal .= $emarking->grade;
	 	} else {
	 		$htmlmodal .= "-";
	  	}
		
	  	$htmlmodal .= "</div>
		  				<div class='col-md-3'>
		  					<b>".get_string('status', 'local_facebook')."</b>
		  					<br>";
		  					
	  	if($emarking->status >= 20) {
	  		$htmlmodal .= get_string('published', 'local_facebook');
	  	} else if($emarking->status >= 10) {
	  		$htmlmodal .= get_string('submitted', 'local_facebook');
	  	} else {
	  		$htmlmodal .= get_string('absent', 'local_facebook');
	  	}
	  	
	  	$htmlmodal .= "</div>
		  				<div class='col-md-3'>
		  					<br>
		  						<a href='".$emarkingurl."' target='_blank'>".get_string('viewexam', 'local_facebook')."</a>
		  				</div>
		  			</div>";
	}
  	
  	echo $htmlmodal;
}