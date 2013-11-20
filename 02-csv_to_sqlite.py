import sys
import csv
import os
import glob
import sqlite3
import pprint

white_list_folder='whitelists' # répertoires avec whites listes déjà acceptées
folder_to_process='to_process' # répertoire avec des csv à tagger
tag_column='' # colonne où on mets les 'x' (selectionner) et les 'w'  supprimer. Si elle n'est pas

for file in glob.glob(white_list_folder+"/*.csv"):
	with open(file, 'rb') as csvfile:
		spamreader = csv.reader(csvfile, delimiter='\t', quotechar='')
		for row in spamreader:
			print ', '.join(row)

