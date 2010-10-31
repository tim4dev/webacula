#!/bin/sh

#########################################################################
# webacula_clean_tmp_files.sh
# Delete webacula old tmp files. Script to be run by the cron.
#
# @package    webacula
# @author Yuri Timofeev <tim4dev@gmail.com>
# @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
#########################################################################

cd /usr/share/webacula/data/tmp && find . -name "webacula*" -type f -mtime -24 -exec rm --force ’{}’ \;

