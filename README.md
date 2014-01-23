# Copyright 2014 David Chavalarias
# Licence: GPL 3+ 

Whitelister
===========

Definition :
------------
Whitelist : a list of keyphrases (white terms) that will be searched and
indexed in your corpora. A white term can have many written forms in
the text (e.g. model, modelling, modeling). A whitelist is a csv file
with at least three columns (names can be changed in the whitelister
settings).:

	* stem: the unique id of the terms, can be the stem of the most common
	form, main form: label for the node which will represent all the forms,
	forms: list of the forms separated by a pre-defined separator (ex |&|).

	* optional : a 'tag' field with 'x' to mark white terms and 'w' to mark
	stopped terms. In http://cortext.org , is it the column 'sort (type "x"
	to keep the word for indexation, "w" to delete it)'

A term object is a tuple (unique_id,main form,forms,tag). The forms item
is a key element for the whitelister. The whitelister will merge any
white term object that has at least one of their forms in common.

A white terms is a term that has at least one white form. So forms is
the level where items are tagged as white or stopped.

synonyms file : a file that defines equivalent forms. Each line is
defining an equivalence as a list of forms comma separated : eg. :
model, modelling, modeling. This file should have the .csv extension.
Each form in a synonym line is considered as white form.

Launch a projet 'MyProject'
--------------------------
    create a MyProject folder in /project with subfolders as in project/project_template
    copy your synonyms files in the folder synonmys/
    copy your existing white lists in the folder whitelists/
    copy the files you want to process in to_process/
    launch whiteListManager.php

Format of the files to process

The file to process should be in csv format (default is delimiter=tab,
enclosure=empty) with at least the three columns describing a term
object : (unique id, main form, forms). All the other columns will be
conserved.

What the whitelister does:
--------------------------
It imports all terms objects described in the three folders : synonyms,
whitelists and to_process.

Terms objects from to_process with at least one of their forms in common
are merged. Terms objects from white lists and synonyms with at least
one of their forms in common are merged.

The whitelister takes all the terms object described in the csv files
of to_process, checks wether one of their forms already appears in the
white lists or has synonyms. If it is the case, the tag 'x' is added to
term object and all the related forms are merged. The label taken as the
main forms is one of the main forms of the merged terms objects.

If one of the forms of a terms object is a stopped form, the tag 'w' is
added, except in case of conflict between white and stopped forms. In
that case, 'x' is keept and a conflict warning is displayed.

A new csv tagged_white_list.csv with all the terms objects is generated
at the root of the project folder with the same structure as the files
to process and an additionnal tag column with the tags.

Optionnaly, the whitelister can include all the terms objects of the
white lists and synonyms in the output file.

Linux commands
--------------
cp -R project/project_template project/MyProject
