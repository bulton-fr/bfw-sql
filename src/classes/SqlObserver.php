<?php
/**
 * Classes en rapport avec les sgdb
 * @author Vermeulen Maxime <bulton.fr@gmail.com>
 * @version 1.0
 */

namespace BFWSql;

use \Exception;

/**
 * Classe gérant l'observeur des requêtes SQL pour créer le log avec les EXPLAIN
 * @package bfw-sql
 */
class SqlObserver extends \BFW\Observer
{
    /**
     * L'action à effectuer quand l'observer est déclanché
     * 
     * @param BFW\Kernel $subject Le sujet observant
     * @param array      $action  Les actions à effectuer
     * 
     * @throws \Exception : Si le paramètre $subject n'est pas un objet ou n'est pas une instance de \BFW\Kernel
     */
    public function updateWithAction($subject, $action)
    {
        if(!is_object($subject))
        {
            throw new Exception('Le paramètre $subject doit être un objet.');
        }
        elseif(is_object($subject) && get_class($subject) != '\BFW\Kernel')
        {
            throw new Exception('Le paramètre $subject doit être un objet de type \BFW\Kernel.');
        }
        
        if(is_array($action))
        {
            if(!empty($action['value']) && $action['value'] == 'REQ_SQL')
            {
                if(!empty($action['REQ_SQL']) && isset($action['REQ_SELECT']))
                {
                    $requete = $action['REQ_SQL'];
                    
                    $req = new Sql();
                    $req->query('FLUSH STATUS;');
                    $res = $req->query('EXPLAIN '.$requete); //$res type ressource ou false.
                    
                    $backtrace = debug_backtrace();
                    $trace = array();
                    
                    foreach($backtrace as $btrace)
                    {
                        $trace[] = $btrace['file'].' : '.$btrace['line'];
                    }
                    
                    logfile(path_kernel.'/log/log_sql.log', '************* DEBUT OPTIMIZE SQL *************');
                    logfile(path_kernel.'/log/log_sql.log', 'BackTrace   : '.print_r($trace, true));
                    logfile(path_kernel.'/log/log_sql.log', 'Requête SQL : '.$requete);
                    
                    if($res === false) {logfile(path_kernel.'/log/log_sql.log', 'EXPLAIN failed');}
                    else
                    {
                        $tmp = $res->fetchAll();
                        $fetch = array();
                        
                        if(isset($tmp[0]))
                        {
                            foreach($tmp[0] as $key => $value)
                            {
                                if(is_numeric($key) === false)
                                {
                                    $fetch[$key] = $value;
                                }
                            }
                            
                            $fetch = print_r($fetch, true);
                        }
                        else
                        {
                            $fetch = 'EXPLAIN VIDE';
                        }
                        
                        logfile(path_kernel.'/log/log_sql.log', $fetch);
                    }
                    
                    $res = $req->query('SHOW STATUS');
                    if($res === false) {logfile(path_kernel.'/log/log_sql.log', 'SHOW STATUS failed');}
                    else
                    {
                        $tmp = $res->fetchAll();
                        $fetch = array();
                        if(!empty($tmp))
                        {
                            foreach($tmp as $tmpValue)
                            {
                                $key = $tmpValue['Variable_name'];
                                $value = $tmpValue['Value'];
                                
                                if(substr($key, 0, 8) == 'Created_' || substr($key, 0, 8) == 'Handler_')
                                {
                                    $fetch[$key] = $value;
                                }
                            }
                            
                            $fetch = print_r($fetch, true);
                        }
                        else
                        {
                            $fetch = 'SHOW STATUS VIDE';
                        }
                        
                        logfile(path_kernel.'/log/log_sql.log', $fetch);
                    }
                    
                    logfile(path_kernel.'/log/log_sql.log', '************* FIN OPTIMIZE SQL *************');
                    logfile(path_kernel.'/log/log_sql.log', '', false);
                }
            }
        }
    }
}
?>