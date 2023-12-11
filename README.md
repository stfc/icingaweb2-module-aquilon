Aquilon Importer Module for Icinga Web 2
========================================

This is a very simple module that gets all Aqulion hosts on site at RAL and imports them into Icinga.
Some on disk caching is used for this, as the profiles can be relatively large - and therefore it can take
a little while to download them all.

This is really designed for the Quattor/Aquilon implementation at STFC RAL - but if you are able to make
use of it, please do!

This import source requires a file containing a list of Aquilon archetypes and personalities, in this format:
<br />archetype/personality<br />
archetype2/personality2

And so on. The location is defined in Icinga Director.

Currently being updated.
