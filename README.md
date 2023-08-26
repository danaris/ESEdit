# ESEdit

There are a number of Endless Sky editor utilities; however, when I had trouble getting some of them to compile at all, and others were built in ways that felt awkward to me. So, what's a developer to do?

Naturally, I built my own.

ESEdit is a web-based viewer and, eventually, editor of the Endless Sky data files. At present, it is built off the parsing code from circa the 0.10.2 release. My intention is for it to eventually support...well, many things it does not currently, but broadly speaking, loading arbitrary plugins, either client-side or scoped to an individual user, and recalculating current state based on saved games.

## Current Functionality

The primary functionality ESEdit currently offers is in two areas:

- Galaxy Map: It will display the full galaxy map, including wormholes and hidden systems. For now, the Pleieades are unavailable offscreen. 
-- System Viewer: There is a rudimentary system viewer with minimal functionality.
- Ships: It has a list of all ships, broken down by category, with their thumbnail or sprite.
-- Ship Viewer: Clicking on a ship in the ship list will display a complete overview of the ship's stats, including its sprite with hardpoints and animation, editable outfits, and the ability to get a data spec for the ship with any modifications you have made.

## Usage

ESEdit is web-based, using the Symfony framework, so if you want to set up your own instance, you will need to create a SQL database for it to connect to and set up the instance itself according to the instructions on the [Symfony website](https://symfony.com/doc/current/setup.html). A reference instance is currently available publicly at https://esedit.topazgryphon.org/sky