<?php
/**
 * Doctrine_Template_Listener_CachableQuery
 * This class is a template listener
 *
 * @author      Stéphane PY <py.stephane1@gmail.com>
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.org
 * @since       1.2
 * @version     $Revision$
 */
class Doctrine_Template_Listener_CachableQuery extends Doctrine_Record_Listener
{
  /**
   * Array of options
   *
   * @var string
   */
  protected $_options = array();

  /**
   * __construct
   *
   * @param string $array
   * @return void
   */
  public function __construct(array $options)
  {
      $this->_options = $options;
  }
  
  
  /**
   * postSave, post saving we unset from cache values
   *
   * @param string $Doctrine_Event
   * @author Stéphane PY
   */
  
  public function postSave(Doctrine_Event $event)
  {
    $deleteNames = $this->__getDeletesName($event);
    $cacheDriver = Doctrine_Manager::getInstance()->getAttribute(Doctrine_Core::ATTR_RESULT_CACHE);
    foreach($deleteNames as $name)
    {
      $cacheDriver->delete($name);
    }
  }
  
  /**
   * preDelete, pre deleting we unset from cache values
   *
   * @param string $Doctrine_Event
   * @author Stéphane PY
   */
  
  public function preDelete(Doctrine_Event $event)
  {
    $deleteNames = $this->__getDeletesName($event);
    $cacheDriver = Doctrine_Manager::getInstance()->getAttribute(Doctrine_Core::ATTR_RESULT_CACHE);
    foreach($deleteNames as $name)
    {
      $cacheDriver->delete($name);
    }
  }
    
  /**
   * return deletesName to clean cache
   *
   * @param Doctrine_Evenet $event 
   * @author Stéphane PY
   * @return array
   */
  private function __getDeletesName(Doctrine_Event $event)
  {
    $toRemoveNames   = $this->_options["remove"];
    $names           = array();
    
    foreach($toRemoveNames as $toRemoveName)
    {
      $name = $this->__formatRemovedName($event, $toRemoveName);
      
      if(is_array($name))
      {
        foreach($name as $nameToAdd)
        {
          $names[] = $nameToAdd;
        }
      }
      else
      {
        $names[] = $name;
      }
    } 
    
    return $names;
  }
  
  /**
   * format name to remove, you can use magic method like:
   * - %id%,                 it's check ->getId() on invoker
   * - %Relations.id% (many) it's check ->getRelations(), and return id of these
   * - %Relation.id% (one)   it's check ->getRelations()->getId()
   *
   * @param Doctrine_event $event 
   * @param string $toRemoveName 
   * @author Stéphane PY
   * @return array or string
   */
  private function __formatRemovedName(Doctrine_Event $event, $toRemoveName)
  {
    //if there is no %xx% on string, we return it
    if(false == preg_match("/\%[a-zA-Z.]*\%/", $toRemoveName, $matches))
      return $toRemoveName;
    
    //get actual object
    $object = $event->getInvoker();
    
    //for all %xxx% we do traitment
    foreach($matches as $match)
    {
      $matchWithoutPercent = str_replace("%", "", $match);       //get string without %%
      $explodedMatches     = explode(".", $matchWithoutPercent); //explode string by .
      $result              = clone $object;
      
      //for all exploded matches, we do get.Camelized match, if it's Doctrine_Collection, we set result as array
      foreach($explodedMatches as $explodedMatch)
      {
        $method = "get".sfInflector::camelize($explodedMatch);
        
        if($result instanceof Doctrine_Collection) //if result is doctrine_collection, we set result as array
        {
          $resultsOk = array();
          foreach($result as $key => $object)
          {
            $resultsOk[] = $object->$method();
          }
          $result = $resultsOk;
        }
        else
        {
          $result = $result->$method(); 
        }
      }
      
      //if result is array, we must replace %xx% too, but duplicate the "toRemoveName"
      if(is_array($result))
      {
        $toRemoveNameBu = $toRemoveName;
        $toRemoveName   = array();
        foreach($result as $resultOfArrat)
        {
          if(is_object($resultOfArrat))
            throw new sfException("Cannot return an object");
          
          $toRemoveName[] = str_replace($match, $resultOfArrat, $toRemoveNameBu); 
        }
      }
      elseif(!is_object($result)) //if is not an object, we just replace %xx% by result
      {
        $toRemoveName = str_replace($match, $result, $toRemoveName); 
      }
      else //if it's an over type, we don't know what to do
      {
        throw new sfException("Cannot return an object");
      }
    }
    
    return $toRemoveName;
  }
}