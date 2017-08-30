<?php
/**
*qdPM
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@qdPM.net so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade qdPM to newer
* versions in the future. If you wish to customize qdPM for your
* needs please refer to http://www.qdPM.net for more information.
*
* @copyright  Copyright (c) 2009  Sergey Kharchishin and Kym Romanets (http://www.qdpm.net)
* @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*/
?>
<?php

/**
 * Users
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @package    sf_sandbox
 * @subpackage model
 * @author     Your name here
 * @version    SVN: $Id: Builder.php 7490 2010-03-29 19:53:27Z jwage $
 */
class Users extends BaseUsers
{
  public static function countRelatedItemsByUsersId($id)
  {
    $count = 0;
    
    $count += Doctrine_Core::getTable('Projects')->createQuery()->addWhere('created_by=?',$id)->count();
    $count += Doctrine_Core::getTable('ProjectsComments')->createQuery()->addWhere('created_by=?',$id)->count();
    $count += Doctrine_Core::getTable('Tasks')->createQuery()->addWhere('created_by=?',$id)->count();
    $count += Doctrine_Core::getTable('TasksComments')->createQuery()->addWhere('created_by=?',$id)->count();
    $count += Doctrine_Core::getTable('Tickets')->createQuery()->addWhere('users_id=?',$id)->count();
    $count += Doctrine_Core::getTable('TicketsComments')->createQuery()->addWhere('users_id=?',$id)->count();
    $count += Doctrine_Core::getTable('Discussions')->createQuery()->addWhere('users_id=?',$id)->count();
    $count += Doctrine_Core::getTable('DiscussionsComments')->createQuery()->addWhere('users_id=?',$id)->count();
    
    return $count;
  }

  public static function addFiltersToQuery($q,$filters)
  {    
    $count_e = 0;
    
    foreach($filters as $table=>$fstr)
    {
      $ids = explode(',',$fstr);
      
      switch($table)
      {
        case 'UsersGroups':
            $q->whereIn('u.users_group_id',$ids);
          break;      
      }

    }
          
    return $q;  
  }
  
  public static function getHiddenReports($id)
  {
    if($u = Doctrine_Core::getTable('Users')->find($id))
    {
      return explode(',',$u->getHiddenCommonReports()); 
    }
    else
    {
      return array();
    }
  }
  
  
  public static function getNameById($id,$separator='<br>',$schema=false)
  {
    $l = array();
                
    foreach(explode(',',$id) as $v)
    {
      if($schema)
      {
        if(isset($schema[$v]))
        {
          $l[] = $schema[$v];
        }
      }
      elseif($u = Doctrine_Core::getTable('Users')->find($v))
      {
        $l[] = $u->getName();
      }
    }
    
    return implode($separator,$l);
  }
  
  public static function getSchema()
  {
    $users =  Doctrine_Core::getTable('Users')->createQuery('u')        
        ->orderBy('u.name')
        ->fetchArray();
        
    $schema = array();
    foreach($users as $u)
    {
      $schema[$u['id']] = $u['name'];
    }
    
    return $schema;
  }
  
  public static function getEmailChoicesByGroupId($id)
  {
    $users_list = Doctrine_Core::getTable('Users')->createQuery('u')
        ->addWhere('u.active=1')
        ->addWhere('u.users_group_id=?',$id)
        ->orderBy('u.name')->fetchArray();
    
    $choices = array();
    foreach($users_list as $u)
    {
      $choices[$u['email']] = $u['name'];
    }
    
    return $choices;
  }
    
  public static function getChoices($include = array(),$has_access = '', $add_empty = false)
  {
    $q = Doctrine_Core::getTable('Users')->createQuery('u')->leftJoin('u.UsersGroups ug')
        ->addWhere('u.active=1')
        ->orderBy('ug.name, u.name');
    
    if(count($include)>0)
    {
      $q->whereIn('u.id',$include);
    } 
    
    switch($has_access)
    {
      case 'tasks': $q->addWhere('ug.allow_manage_tasks>0');
        break;
      case 'tasks_insert': $q->addWhere("ug.allow_manage_tasks = 1 ");
        break;
      case 'tasks_comments_insert': $q->addWhere("ug.allow_manage_tasks>0");
        break;       
      case 'tickets': $q->addWhere('ug.allow_manage_tickets>0');
        break;
      case 'tickets_insert': $q->addWhere("ug.allow_manage_tickets=1");
        break;
      case 'discussions': $q->addWhere('ug.allow_manage_discussions>0');      
        break;
      case 'discussions_insert': $q->addWhere("ug.allow_manage_discussions=1");
        break;
    }   
    
    $l = $q->fetchArray();
    $choices = array();
    
    if($add_empty) $choices[''] = '';
        
    foreach($l as $v)
    {       
      $choices[$v['UsersGroups']['name']][$v['id']]=$v['name'];
    }
    
    return $choices;
  }
  
  public static function getRandomPassword($passwordLength = 7)
  {
    $passwordChars = "abcdefghijkmnopqrstuvwxyz023456789ABCDEFGHIJKMNOPQRSTUVWXYZ";

    $password = '' ;
    
    for($i=0; $i<$passwordLength; $i++)
    {
        $password .= $passwordChars[rand(0,58)];
    }
    
    return $password;
  }
  
  static public function sendEmail($from, $to, $subject, $body, $user=false,$use_single_email=true,$check_active_users = true)
  {
    if(sfConfig::get('app_use_email_notification')=='off') return false;
      
    if(strlen(sfConfig::get('app_email_label'))>0)
    {
      $subject = sfConfig::get('app_email_label') . ' ' . $subject;
    }
    
    if(sfConfig::get('app_use_single_email')=='on' and $use_single_email)
    {
      if(strlen(sfConfig::get('app_single_email_addres_from'))>0 and strlen(sfConfig::get('app_single_name_from'))>0)
      {
        $from = array(sfConfig::get('app_single_email_addres_from')=>sfConfig::get('app_single_name_from'));
      }
    }
      
    if(sfConfig::get('app_use_smtp')=='on')
    {    
      $configuration = array('class'=>'sfMailer',
                            'logging'=>'1',
                            'charset'=>'utf-8',
                            'delivery_strategy'=>'realtime',
                            'transport'=>
                            array('class'=>'Swift_SmtpTransport',
                                  'param'=>
                                  array('host'=>sfConfig::get('app_smtp_server'),
                                        'port'=>sfConfig::get('app_smtp_port'),
                                        'encryption'=>sfConfig::get('app_smtp_encryption'),
                                        'username'=>sfConfig::get('app_smtp_login'),
                                        'password'=>sfConfig::get('app_smtp_pass'))));
    }
    else
    {
      $configuration = array('class'=>'sfMailer',
                            'logging'=>'1',
                            'charset'=>'utf-8',
                            'delivery_strategy'=>'realtime',
                            'transport'=>
                            array('class'=>'Swift_MailTransport'));
    }
    
    sfContext::getInstance()->setMailerConfiguration($configuration);
    
    $mailer = sfContext::getInstance()->getMailer();
          
    //don't send emails to inactive users
    if($check_active_users)
    {
      foreach($to as $toEmail=>$toName)
      {
        if(!Doctrine_Core::getTable('Users')->createQuery()->addWhere('active=1')->addWhere('email=?',$toEmail)->fetchOne())
        {
          unset($to[$toEmail]);
        }
      }
    }
    
    $html = str_replace(array("\r\n", "\n", "\r"),"",$body);  
    $text = strip_tags(str_replace(array('<br>','<p>','</p>','<h3>','</h3>','<tr>'),"\n",str_replace(array("\r\n", "\n", "\r"),"",$body)));
            
    foreach($to as $toEmail=>$toName)
    {                                              
      $message = sfContext::getInstance()->getMailer()->compose();
      $message->setSubject($subject);
      $message->setTo(array($toEmail=>$toName));
      $message->setFrom($from); 
      $message->setBody($html, 'text/html');
      $message->addPart($text, 'text/plain'); 
                              
      try
      {                
        @$mailer->send($message);
      } 
      catch (Exception $e)
      {
        if($user)
        {
          $user->setFlash('userNotices', array('type'=>'error','text'=>'Error sending email: ' . $e->getMessage()));
        }
        else
        {
          die($e->getMessage());
        }                                                       
      }           
    } 
    
    //echo $html;
    //exit();
        
  }

  
  public static function checkAccess($c,$access,$module,$sf_user,$projects_id=false)
  {
    if(!Users::hasAccess($access,$module,$sf_user,$projects_id))
    {
      $c->redirect('accessForbidden/index');
    }
  }
  
  public static function hasProjectsAccess($access, $sf_user, $projects)
  {
    if(Users::hasAccess($access,'projects',$sf_user,$projects->getId()) and Projects::hasViewOwnAccess($sf_user,$projects))
    {
      return true;
    }
    else
    {
      return false;
    }
  }
  
  public static function hasTasksAccess($access, $sf_user, $tasks, $projects)
  {
    if(Users::hasAccess($access,'tasks',$sf_user,$projects->getId()) and Tasks::hasViewOwnAccess($sf_user,$tasks,$projects))
    {
      return true;
    }
    else
    {
      return false;
    }
  }
  
  public static function hasTicketsAccess($access, $sf_user, $tasks, $projects=null)
  {
    if($projects)
    {
      if(Users::hasAccess($access,'tickets',$sf_user,$projects->getId()) and Tickets::hasViewOwnAccess($sf_user,$tasks,$projects))
      {
        return true;
      }
      else
      {
        return false;
      }
    }
    else
    {
      if(Users::hasAccess($access,'tickets',$sf_user) and Tickets::hasViewOwnAccess($sf_user,$tasks))
      {
        return true;
      }
      else
      {
        return false;
      }
    }
  }
  
  public static function hasDiscussionsAccess($access, $sf_user, $discussions, $projects)
  {
    if(Users::hasAccess($access,'discussions',$sf_user,$projects->getId()) and Discussions::hasViewOwnAccess($sf_user,$discussions,$projects))
    {
      return true;
    }
    else
    {
      return false;
    }
  }
  
  public static function hasAccess($access,$module,$sf_user,$projects_id=false)
  {
    $schema = Users::getAccessSchema($module,$sf_user,$projects_id);
          
    if(strstr($access,'|'))
    {
      foreach(explode('|',$access) as $a)
      {
        if($schema[$a])
        {
          return true;
        }
      }
    }
    elseif($schema[$access])
    {
      return true;
    }
    
    return false;    
  }
    
  public static function getAccessSchema($module,$sf_user,$projects_id=false)
  {
    $access = array();
    $custom_access = array();
            
    $schema = array('view'      =>false,
                    'view_own'  =>false,                    
                    'insert'    =>false,
                    'edit'      =>false,
                    'delete'    =>false);
    
    if($sf_user->getAttribute('users_group_id')==0)
    {
      return $schema;
    }
                    
    $user = $sf_user->getAttribute('user');
    $usersGroups = $user->getUsersGroups();
                            
    switch($module)
    {
      case 'projects':          
          $access = $usersGroups->getAllowManageProjects();           
        break;
      case 'tasks':          
          $access = $usersGroups->getAllowManageTasks();           
        break;
      case 'tickets':          
          $access = $usersGroups->getAllowManageTickets();           
        break;
      case 'discussions':          
          $access = $usersGroups->getAllowManageDiscussions();           
        break;
      case 'projectsComments':
          $access = $usersGroups->getAllowManageProjects();
        break;
     case 'tasksComments':
          $access = $usersGroups->getAllowManageTasks();
        break;
     case 'ticketsComments':
          $access = $usersGroups->getAllowManageTickets();
        break;
     case 'discussionsComments':
          $access = $usersGroups->getAllowManageDiscussions();
        break;  
    }
    
    if(strstr($module,'Comments'))
    {      
      if($access>0)
      {
        $schema = array('view'      =>true,
                        'view_own'  =>true,                            
                        'insert'    =>true,
                        'edit'      =>true,
                        'delete'    =>true);
      }
    }
    else
    {
      switch($access)
      {    
        //full access
        case '1':     
            $schema = array('view'      =>true,
                            'view_own'  =>false,                            
                            'insert'    =>true,
                            'edit'      =>true,
                            'delete'    =>true);
          break;     
        //view only             
        case '2':     
            $schema = array('view'      =>true,
                            'view_own'  =>false,                            
                            'insert'    =>false,
                            'edit'      =>false,
                            'delete'    =>false);
          break;   
        //view own only       
        case '3':     
            $schema = array('view'      =>true,
                            'view_own'  =>true,                            
                            'insert'    =>false,
                            'edit'      =>false,
                            'delete'    =>false);
          break;
        //manage_own_lnly  
        case '4':     
            $schema = array('view'      =>true,
                            'view_own'  =>true,                            
                            'insert'    =>true,
                            'edit'      =>true,
                            'delete'    =>true);
          break;
      }   
    }
    
    //print_r($schema);
    
    return $schema;
  }
}
