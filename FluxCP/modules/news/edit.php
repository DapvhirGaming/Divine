<?php
if (!defined('FLUX_ROOT')) exit;
$this->loginRequired();
$title = Flux::message('XCMSNewsEditTitle');
$news = Flux::config('FluxTables.CMSNewsTable');
$id	= $params->get('id');
$sql	= "SELECT * FROM {$server->loginDatabase}.$news WHERE id = ?";
$sth	= $server->connection->getStatement($sql);
$sth->execute(array($id));
$new_s = $sth->fetch();
if($new_s) {
    $title = $new_s->title;
    $body	= $new_s->body;
    $link	= $new_s->link;
    $author = $new_s->author;
    
    if(count($_POST)) {
        $title	= trim($params->get('news_title'));
        $body 	= trim($params->get('news_body'));
		$link 	= trim($params->get('news_link'));
		$author = trim($params->get('news_author'));
        
        if($title === '') {
            $errorMessage = Flux::Message('XCMSNewsTitleError');
        }
        elseif($body === '') {
            $errorMessage = Flux::Message('XCMSNewsBody');
        }
		elseif($author == '') {
				 $errorMessage = Flux::Message('XCMSNewsAuthor');
		}
		else {
			if($link) {
				if (!preg_match('!^http://!i', $news_link)) {
					$news_link = "http://$news_link";
				}
			}
			
			$sql = "UPDATE {$server->loginDatabase}.$news SET ";
			$sql .= "title = ?, body = ?, link = ?, author = ?, modified = NOW() ";
			$sql .= "WHERE id = ?";
			$sth = $server->connection->getStatement($sql);
			$sth->execute(array($title, $body, $link, $author, $id));
			
			$session->setMessageData(Flux::message('XCMSNewsUpdated'));
			if ($auth->actionAllowed('news', 'index')) {
				$this->redirect($this->url('news','index'));
			}
			else {
				$this->redirect();
			}           
		}
    }
}
?>