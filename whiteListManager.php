<?php
echo '<meta http-equiv="Content-type" content="text/html; charset=UTF-8"/>';
/*
 * prend un répertoire white liste et un répertoire 'to_process' de csv à merger et pré-tagge les champs. 
Conserve les groupements des white liste existantes. Si deux lignes ont une forme en commun, elles
seront mergées.
On peut mettre des synonymes dans le répertoire synonyms, tous les termes synonymes seront fusionnés au niveau des formes.
 */

$debug_strong='detention centers';

$delimiter = "\t";
$enclosure = ' ' ;
$enclosure_out='"'; // pour le fichier de sortie

$project_name='rock';

$white_list_folder='whitelists';// répertoires avec whites listes déjà acceptées
$folder_to_process='to_process'; // répertoire avec des csv à tagger
$synonyms_folder='synonyms';

// colonnes obligatoires ///////
$tag_column='sort (type "x" to keep the word for indexation, "w" to delete it)'; // colonne où on mets les 'x' (selectionner) et les 'w'  supprimer. Si elle n'est pas
//spécifiée ou n'existe pas, on prends la première colonne où il y a des 'x'.
$forms_col='forms'; // colonne stockant les formes à considérer
$unique_id='stem'; // colonne avec identifiants unique des formes
$main_form='main form';
///////////////:

$white_forms=array(); // liste des formes rencontrées dans les whites lists
$stopped_forms=array();// liste des formes stoppées (w)
$forms_sep='|&|'; // separateur de formes
$keep_all_whitewords=1;// si 0, tagge simplement les éléments de $folder_to_process, sinon importe également l'ensemble des termes des white lists
$out_file='tagged_white_list.csv';



$valid_forms=0; // count the number of pretagged forms
$stop_forms=0;// count the number of stopped forms
$main_form_count=0;
$group_count=0;

$merged_lists=array();// white liste finale à écrire

echo '-------- synonyms ---------';
// on commence par importer des synonymes. Ils seront écrasés par les lignes suivantes si utilisés
pt('processing '.'projects'.'/'.$project_name.'/'.$synonyms_folder.' as synonyms source');
foreach (glob('projects'.'/'.$project_name.'/'.$synonyms_folder . "/*.csv") as $to_analyse) {
    if (($handle = fopen($to_analyse, "r","UTF-8")) !== FALSE) {    
        pt('importing '.$to_analyse);
        while (($line= fgetcsv($handle, 4096,',')) !== false) {
            // on stocke toutes les formes et on fait un pré-fichier de white liste
            $newline=array();// nouvelle ligne à mettre dans le tableau définitif avec les champs obligatoires : 0->tag, 1->stem, 2->main form, 3->forms

            $key=trim($line[0]); // key to store this stem in the $merged_lists  
            ///pta($line);          
            $forms=implode($forms_sep, $line);
            ///pt($forms);
            foreach ($line as $key1 => $form) {
                $white_forms[trim($form)]=1;
            }                    
            // modify merged list
            if (array_key_exists($key,$merged_lists)){// on merge les regroupement
                $existant_forms=explode($forms_sep,$merged_lists[$key][3]);
                $added_forms=$line;
                $final_forms=implode('|&|', array_unique(array_merge($added_forms,$existant_forms))); 
                $group_count+=1;
                $merged_lists[$key][3]=$final_forms;                                        
            }else{
                $newline[0]='x';
                $newline[1]=$key;
                $newline[2]=$key;
                $newline[3]=$forms;
                $merged_lists[$key]=$newline;                   
            }       

        }                         
    }
    fclose($handle);      
}
ptbg('synonyms --------------');
ptabg($merged_lists);
// on extrait toutes les formes valides
pt('processing '.'projects'.'/'.$project_name.'/'.$white_list_folder.' as white list source');
foreach (glob('projects'.'/'.$project_name.'/'.$white_list_folder . "/*.csv") as $to_analyse) {
    $raw_num=0;
    if (($handle = fopen($to_analyse, "r","UTF-8")) !== FALSE) {    
        pt('importing '.$to_analyse);
        while (($line= fgetcsv($handle, 4096,$delimiter)) !== false) {
            if ($raw_num==0){// analyse de la première ligne pour trouver les emplacements de colonnes obligatoires
                $tag_column_number=array_search($tag_column, $line);
                pt("tag field:".$tag_column_number);
                $forms_col_number=array_search($forms_col, $line);
                pt("forms field:".$forms_col_number);
                $unique_id_column=array_search($unique_id, $line);                 
                pt('unique id:'.$unique_id_column);
                $main_form_column=array_search($main_form, $line);                 
                $raw_num+=1;                
            }else{// on stocke toutes les formes et on fait un pré-fichier de white liste
                $newline=array();// nouvelle ligne à mettre dans le tableau définitif avec les champs obligatoires : 0->tag, 1->stem, 2->main form, 3->forms

                $key=trim($line[$unique_id_column]); // key to store this stem in the $merged_lists
                //$key=str_replace(' ','_', $key);
                //$key=str_replace('.','', $key);
                //$key=str_replace(',','', $key);
                if ($tag_column_number>-1){   // il y a une de colonne 'x' on ne considere que celles qui on un 'x'                    
                    if (trim($line[$tag_column_number])=='x'){                           
                        // modify white forms               
                        $forms=explode($forms_sep, $line[$forms_col_number]);
                        foreach ($forms as $key1 => $form) {
                            $white_forms[trim($form)]=1;
                        }
                        // modify merged list
                        if (array_key_exists($key, $merged_lists)){// on merge les regroupement
                            $existant_forms=explode($forms_sep,$merged_lists[$key][$forms_col_number]);
                            $added_forms=explode($forms_sep,$line[$forms_col_number]);
                            $final_forms=implode('|&|', array_unique(array_merge($added_forms,$existant_forms)));
                            $merged_lists[$key][3]=$final_forms; 
                            $merged_lists[$key][0]='x';
                            $group_count+=1;                         
                        }else{   
                            $newline[0]='x';
                            $newline[1]=$line[$unique_id_column];
                            $newline[2]=$line[$main_form_column];
                            $newline[3]=$line[$forms_col_number];
                            $merged_lists[$key]=$newline;                                                    
                        } 
                    }elseif (trim($line[$tag_column_number][0])=='w'){   
                        // modify stopped forms          
                        $forms=explode($forms_sep, $line[$forms_col_number]);
                        foreach ($forms as $key1 => $form) {
                            $stopped_forms[trim($form)]=1;
                        }
                    }

                }else{
                    // il n'y a pas de colonne 'x' on prend toutes les lignes
                    // modify white forms

                    $forms=explode($forms_sep, $line[$forms_col_number]);                    
                    foreach ($forms as $key1 => $form) {
                    $white_forms[trim($form)]=1;
                    }                                            
                    // modify merged list
                    if (array_key_exists($key,$merged_lists)){// on merge les regroupement
                        $existant_forms=explode($forms_sep,$merged_lists[$key][$forms_col_number]);
                        $added_forms=explode($forms_sep,$line[$forms_col_number]);
                        $final_forms=implode('|&|', array_unique(array_merge($added_forms,$existant_forms)));
                        $group_count+=1;                        
                        $merged_lists[$key][3]=$final_forms;                        
                    }else{
                        $newline[0]='x';
                        $newline[1]=$line[$unique_id_column];
                        $newline[2]=$line[$main_form_column];
                        $newline[3]=$line[$forms_col_number];
                        $merged_lists[$key]=$newline;                   
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

pt(count($merged_lists).' terms in existing white lists');
flush();
// rewrite of candidate white lists with merge

$header_writen=0;
$final_white_list=array();

$merged_list_formated=false;

pt('processing '.'projects'.'/'.$project_name.'/'.$folder_to_process.' as list to process');
flush();
foreach (glob('projects'.'/'.$project_name.'/'.$folder_to_process. "/*.csv") as $to_analyse) {
    // !!!! pour le moment, on fait l'hypothèse que tous les fichiers à traiter sont dans le même format
    $raw_num=0;
    if (($handle = fopen($to_analyse, "r","UTF-8")) !== FALSE) {    
        while (($line= fgetcsv($handle, 4096,$delimiter)) !== false) {
            if ($raw_num==0){// analyse de la première ligne
                $tag_column_number=array_search($tag_column, $line);
                pt('Tag : '.$tag_column_number);
                $forms_col_number=array_search($forms_col, $line);
                pt('Form : '.$forms_col_number);                
                $unique_id_column=array_search($unique_id, $line);
                pt('unique id column : '.$unique_id_column);
                $main_form_column=array_search($main_form, $line);   
                
                if (!$merged_list_formated){// intègre les whites listes au nouveau format traité
                    $merged_list_formated=true;
                    $line_template=$line; 
                    foreach ($line_template as $key => $value) {
                        $line_template[$key]='';
                    }
                    foreach ($merged_lists as $key => $value) {
                        $final_white_list[$key]=$line_template;
                        $final_white_list[$key][0]='x';
                        $final_white_list[$key][$unique_id_column+1]=$value[1];
                        $final_white_list[$key][$main_form_column+1]=$value[2];
                        $final_white_list[$key][$forms_col_number+1]=$value[3];
                    }                    
                }
                ptabg($final_white_list);
                $raw_num+=1;                
                if ($header_writen==0){
                    $header_writen+=1;
                    $header=$line;
                    array_unshift($header,'tag');                    
                }
            }else{// check forms 
                $main_form_count+=1;                                 
                $forms=explode($forms_sep, $line[$forms_col_number]);                
                $ok=0; // on l'accepte
                $stopped=0; // we reject
                foreach ($forms as $key => $form) {
                    if (array_key_exists(trim($form), $white_forms)){                        
                        $ok=1;                        
                    }   
                    if (array_key_exists(trim($form), $stopped_forms)){                        
                        $stopped=1;                        
                    }              
                }
                if ($line[$tag_column_number]=='x'){
                    $ok=1;
                }
                $key=trim($line[$unique_id_column]);
                //$key=str_replace(' ','_', $key);
                //$key=str_replace('.','', $key);
                //$key=str_replace(',','', $key);
                
                //pta(array_unshift($line,'x'));
                //pta(array_unshift($line,'x'));  
                if (($ok==1)&&($line[$tag_column_number]!='g')){// g = déjà regroupé
                    //pt('In White liste :'.$line[$forms_col_number]);      
                    $valid_forms+=1;
                    array_unshift($line,'x');
                    if (array_key_exists($key, $final_white_list)){// on merge les regroupement
                        $existant_forms=explode($forms_sep,$final_white_list[$key][$forms_col_number+1]);
                        $added_forms=explode($forms_sep,$line[$forms_col_number+1]);
                        $final_forms=implode('|&|', array_unique(array_merge($added_forms,$existant_forms)));
                        
                        $final_white_list[$key]=$line;
                        $final_white_list[$key][$forms_col_number+1]=$final_forms;
                        $group_count+=1;                                                                     
                    }else{
                        $final_white_list[$key]=$line;                        
                    }                    
                }elseif($stopped==1){
                    $stop_forms+=1;
                    array_unshift($line,'w');
                    if (array_key_exists($key, $final_white_list)){// on merge les regroupement
                        if ($final_white_list[$key][0]=='x'){
                            pt('warning conflit in tagging with '.$final_white_list[$key][$forms_col_number].' accepted and'.$line[$forms_col_number+1].' rejected');
                            flush();
                        }else{
                            $existant_forms=explode($forms_sep,$final_white_list[$key][$forms_col_number+1]);
                            $added_forms=explode($forms_sep,$line[$forms_col_number+1]);
                            $final_forms=implode('|&|', array_unique(array_merge($added_forms,$existant_forms)));
                            $final_white_list[$key]=$line;
                            $final_white_list[$key][$forms_col_number]=$final_forms;                                                     
                        }
                                           
                    }else{
                        $final_white_list[$key]=$line;                        
                    }
                }else{
                    array_unshift($line,$line[$tag_column_number]);
                    $final_white_list[$key]=$line;
                    
                }                
            }                         
        }        
    }
    fclose($handle);

}
//pt('white keepts');
//pta($final_white_list);


/// removing trailong spaces (should be optimized well before)
foreach ($final_white_list as $key => $value) {
    $forms=explode($forms_sep,$value[$forms_col_number+1]);  
    foreach ($forms as $key2 => $form) {
        $forms[$key2]=trim($form);        
    }    
    $final_white_list[$key][$forms_col_number+1]=implode('|&|',$forms);
}

ptbg('final merged list');
ptabg($final_white_list);

//////////////////////////
/// Grouping  ////////////
//////////////////////////
// On mets ensemble toutes les lignes qui partagent au moins une forme
$forms_map=array(); // tableau dont les clé sont les formes et les valeurs les formes principales
$output_white_list=$final_white_list;

foreach ($final_white_list as $key => $value) {// on parcours les lignes
    //pt('processing '.$key);
    ptbg('$output_white_list');
    ptabg($output_white_list);
    if ($value[0]!='g'){// si elle n'est pas déjà groupée
        //pt($value[$forms_col_number+1]);
        $forms=explode($forms_sep,$value[$forms_col_number+1]);
        ptbg('$forms');
        ptabg($forms);
        $lines2group=array();// list des clé des lignes de $final_white_list qu'il faudra grouper
           foreach ($forms as $key1 => $form) {
        if (array_key_exists($form,$forms_map)){
            $lines2group[]=$forms_map[$form];    
        }else{
            $forms_map[$form]=$value[$unique_id_column+1];
        }        
    }
        ptbg('$forms_map');
        ptabg($forms_map);
        ptbg($key.' to group with');ptabg($lines2group);
        if (count($lines2group)>0){// on regroupe les lignes concernées
            //pt('grouping');
            $line=$value;
            $forms2add=array();
            foreach ($lines2group as $key2group) {
                $existant_forms=explode($forms_sep,$output_white_list[$key2group][$forms_col_number+1]);
                $forms2add=array_merge($forms2add,$existant_forms);    
                $group_count+=1;        
            }
            $added_forms=explode($forms_sep,$line[$forms_col_number+1]);
            //pta(array_unique(array_merge($added_forms,$forms2add)));
            $final_forms=implode('|&|', array_unique(array_merge($added_forms,$forms2add)));
            //pt('final forms:'.$final_forms);
            $output_white_list[$key][$forms_col_number+1]=$final_forms;
            foreach ($lines2group as $key2) {// 
                $output_white_list[$key2][0]='g';
                // puis on rectifie le mapping formes : clé uniques
                //pt('clé:'.$key2);
                $to_remap=array_keys($forms_map,$key2);
                //pt('remap');
                //pta($to_remap);
                foreach ($to_remap as $form2 => $value4) {
                    $forms_map[$value4]=$key;  
                }
            }
            //pt('forms maps');
            //print_r($forms_map);

        }else{
            $output_white_list[$key]=$value;
        }   
    }
    
}
//pt('');
//pt('final white');
//pta($output_white_list);

// write the final list
$output= fopen('projects'.'/'.$project_name.'/'.$out_file, "w","UTF-8");
// header
fputcsv($output,$header,$delimiter,$enclosure_out); 

foreach ($output_white_list as $key => $value) {
    //pta($value);
    fputcsv($output,$value,$delimiter,$enclosure_out); 
}
fclose($output);

pt($main_form_count.' forms processed in new white lists with '.count($merged_lists).' white forms unique in the final list ' .$valid_forms.' pretagged and '.$stop_forms.' stopped and '.$group_count.' grouped');

function pta($array){
    print_r($array);
    echo '<br/>';
}

function pt($string){
    echo $string.'<br/>';
}
 
function ptabg($array){// debug variant
    //print_r($array);
    //echo '<br/>';
}

function ptbg($string){// debug variant
    //echo $string.'<br/>';
}
 

?>
