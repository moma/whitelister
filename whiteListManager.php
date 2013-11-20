<?php
echo 'toto';
echo '<meta http-equiv="Content-type" content="text/html; charset=UTF-8"/>';
/*
 * prend un répertoire white liste et un répertoire de csv à merger et pré-tagge les champs. 
Conserve pas les groupements des white liste existantes.
 */
include("../common/library/fonctions_php.php");

// variables
$delimiter = "\t";
$enclosure = [] ;
$white_list_folder='whitelists';// répertoires avec whites listes déjà acceptées
$folder_to_process='to_process'; // répertoire avec des csv à tagger
$tag_column='sort (type "x" to keep the word for indexation'; // colonne où on mets les 'x' (selectionner) et les 'w'  supprimer. Si elle n'est pas
//spécifiée ou n'existe pas, on prends la première colonne où il y a des 'x'.
$white_forms=array(); // liste des formes rencontrées dans les whites lists
$forms_col='forms'; // colonne stockant les formes à considérer
$unique_id='stem'; // colonne avec identifiants unique des formes
$forms_sep='|&|'; // separateur de formes
$keep_all_whitewords=1;// si 0, tagge simplement les éléments de $folder_to_process, sinon garde l'ensemble des white terms
$out_file='tagged_white_list.csv';



$valid_forms=0; // count the number of pretagged forms
$main_form_count=0;

$merged_lists=array();// white liste finale à écrire

// on extrait toutes les formes valides
foreach (glob($white_list_folder . "/*.csv") as $to_analyse) {
    $raw_num=0;
    if (($handle = fopen($to_analyse, "r","UTF-8")) !== FALSE) {    
        while (($line= fgetcsv($handle, 4096,$delimiter)) !== false) {
            if ($raw_num==0){// analyse de la première ligne
                pta($line);
                $tag_column_number=array_search($tag_column, $line);
                $forms_col_number=array_search($forms_col, $line);
                pt('TAG: '.$tag_column_number);
                $raw_num+=1;                
            }else{// on stocke toutes les formes et on fait un pré-fichier de white liste
                $unique_id_column=array_search($unique_id, $line); 
                $key=$line[$unique_id_column]; // key to store this stem in the $merged_lists
                $key=str_replace(' ','_', $key);
                $key=str_replace('.','', $key);
                $key=str_replace(',','', $key);

                if (!$tag_column_number){// il n'y a pas de colonne 'x' on prend toutes les lignes
                    // modify white forms
                    $forms=split($forms_sep, $line[$forms_col_number]);
                    foreach ($forms as $key => $form) {
                        $white_forms[trim($form)]=1;
                    }                    
                    // modify merged list
                    if (array_key_exists($key, $merged_lists)){// on merge les regroupement
                        $existant_forms=explode($forms_sep,$merged_lists[$key][$forms_col_number]);
                        $added_forms=explode($forms_sep,$line[$forms_col_number+1]);
                        $final_forms=implode('|&|', array_unique(array_merge($added_forms,$existant_forms)));
                        $line[$forms_col_number+1]=$final_forms;
                        $merged_lists[$key]=$line;                        
                    }else{
                        $merged_lists[$key]=$line;                        
                    }          

                }else{// il y a une de colonne 'x' on ne considere que celles qui on un 'x'
                    if ($line[$tag_column_number]=='x'){     
                        // modify white forms               
                        $forms=explode($forms_sep, $line[$forms_col_number]);
                        foreach ($forms as $key => $form) {
                            $white_forms[trim($form)]=1;
                        }
                        // modify merged list
                        if (array_key_exists($key, $merged_lists)){// on merge les regroupement
                            $existant_forms=explode($forms_sep,$merged_lists[$key][$forms_col_number]);
                            $added_forms=explode($forms_sep,$line[$forms_col_number+1]);
                            $final_forms=implode('|&|', array_unique(array_merge($added_forms,$existant_forms)));
                            $line[$forms_col_number+1]=$final_forms;
                            $merged_lists[$key]=$line;                        
                        }else{
                            $merged_lists[$key]=$line;                        
                        } 
                    }
                }                
            }                         
        }        
    }
    fclose($handle);
}
//$white_forms=array_keys($white_forms);

if ($keep_all_whitewords==0){
    $merged_lists=array();// white liste finale à écrire
}

// rewrite of candidate white lists with merge

$header_writen=0;


foreach (glob($folder_to_process. "/*.csv") as $to_analyse) {
    $raw_num=0;
    if (($handle = fopen($to_analyse, "r","UTF-8")) !== FALSE) {    
        while (($line= fgetcsv($handle, 4096,$delimiter)) !== false) {
            if ($raw_num==0){// analyse de la première ligne
                pta($line);
                $tag_column_number=array_search($tag_column, $line);
                $forms_col_number=array_search($forms_col, $line);
                $unique_id_column=array_search($unique_id, $line);
                $raw_num+=1;                
                if ($header_writen==0){
                    $header_writen+=1;
                    $header=$line;
                    array_unshift($header,'tag');                    
                }
            }else{// check forms 
                $main_form_count+=1;                                 
                $forms=explode($forms_sep, $line[$forms_col_number]);
                $ok=0;
                foreach ($forms as $key => $form) {
                    if (array_key_exists(trim($form), $white_forms)){                        
                        $ok=1;                        
                    }                
                }
                if ($line[$tag_column_number]=='x'){
                    $ok=1;
                }
                $key=$line[$unique_id_column];
                $key=str_replace(' ','_', $key);
                $key=str_replace('.','', $key);
                $key=str_replace(',','', $key);
                
                //pta(array_unshift($line,'x'));
                //pta(array_unshift($line,'x'));  
                if (($ok==1)&&($line[$tag_column_number]!='g')){// g = déjà regroupé
                    //pt('In White liste :'.$line[$forms_col_number]);      
                    $valid_forms+=1;
                    array_unshift($line,'x');
                    if (array_key_exists($key, $merged_lists)){// on merge les regroupement
                        $existant_forms=explode($forms_sep,$merged_lists[$key][$forms_col_number]);
                        $added_forms=explode($forms_sep,$line[$forms_col_number+1]);
                        $final_forms=implode('|&|', array_unique(array_merge($added_forms,$existant_forms)));
                        $line[$forms_col_number+1]=$final_forms;
                        $merged_lists[$key]=$line;                        
                    }else{
                        $merged_lists[$key]=$line;                        
                    }                    
                }else{
                    array_unshift($line,$line[$tag_column_number]);
                    $merged_lists[$key]=$line;
                    
                }                
            }                         
        }        
    }
    fclose($handle);
}
//pta($merged_lists);
// write the final list
$output= fopen($out_file, "w","UTF-8");
// header
fputcsv($output,$header,$delimiter); 

foreach ($merged_lists as $key => $value) {
    //pta($value);
    fputcsv($output,$value,$delimiter); 
}
fclose($output);

pt($main_form_count.' forms processed with '.count($merged_lists).' forms unique and ' .$valid_forms.' pretagged');


 
?>
