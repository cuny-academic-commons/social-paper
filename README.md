#Social Paper

A tool for networking in-progress student writing and feedback across disciplines, institutions, academic terms, and publics. Social Paper is currently in development at the CUNY Graduate Center by the [CUNY Academic Commons](https://commons.gc.cuny.edu) development team and funded by a 2014 National Endowment for the Humanities Digital Start Up Grant and a 2015 CUNY Advance Grant. 

* [Project Overview](#project-overview)
* [Technical Overview](#technical-overview)

##<a name="project-overview"></a>Project Overview

###Why social? 

Despite many advances in public forms of pedagogy, student writing is still largely conceived of as a waste product—a valueless byproduct of the production of literate citizens. Current technologies for composing and submitting student writing reinforce this attitude by making it difficult to generate a sustainable public for student work. Though profit-driven social technologies offer new public opportunities, they continue to alienate students from understanding and directing the ways in which technologies shape the social potential of their work.

###A networked writing environment

As a networked writing environment, Social Paper will enable students to compose, archive and share all forms of their written work, whether for class or extracurricular interest. Unlike many learning management systems or course blogs, Social Paper gives students full control over the sharing settings of each individual piece of writing. Students may choose to share a paper with a professor, a class, a writing group, the public at large, or alternately, keep it private as part of their personal, in-progress, reflective writing portfolio. Additionally, while composing, students can post comments on their writing with questions mentioning other users or tagging topics in order to solicit peer feedback or interest. By giving students a centralized space to manage the totality of their writing, students can easily change privacy settings as they mature as writers and thinkers, develop audience for their growing body of work, and reflectively build off prior writing.

###Opening the black box of education

For the most part, student writing is confined to the audience of a single professor and has few opportunities to generate an engaged public beyond each individual course. Social Paper will provide a sustainable commons where students may browse, comment upon, and build off the work of their peers, both within and outside their courses, disciplines, institutions and familiar communities. Social Paper will use activity feeds to promote student writing and student comments among a network of peers; likewise students may choose to associate their papers with categories and topics to make them easily discoverable or showcase them on their public archive. By exposing the hidden messy processes of developing one’s writing and thoughts, Social Paper will provide a space for cultivating egalitarian peer pedagogy. Unlike siloed or ephemeral course sites, Social Paper transforms every writing assignment into the opportunity to build community both within and beyond the class.

###Get involved

Social Paper will be released first as a feature of the CUNY Academic Commons, and in the future will be abstracted as a plugin intended for integration with Commons In A Box (CBOX). Interested educators or students outside of The CUNY Graduate Center community will need to set up a WordPress/BuddyPress or CBOX installation to use Social Paper for their community. Team members are currently exploring ways that Social Paper might be released for general public use without a CBOX install. Individuals interested in using, contributing to, or learning more about Social Paper, are invited to contact Erin Glass at erin (dot) glass (at) gmail (dot) com. 

##<a name="technical-overview"></a>Technical Overview

Social Paper is a WordPress plugin, designed to work best when installed alongside [BuddyPress](https://buddypress.org).

### Prerequisites

Social Paper requires a few additional components to work correctly:

* [WordPress Front End Editor](https://wordpress.org/plugins/wp-front-end-editor/) - Note that the mainline version of WP-FEE does not fully support more recent versions of WordPress. See [https://github.com/iseulde/wp-front-end-editor/issues/257](https://github.com/iseulde/wp-front-end-editor/issues/257) for more details.
* An inline-commenting plugin. Currently, Social Paper supports [WP Side Comments](https://github.com/richardtape/wp-side-comments) and [Inline Comments](https://wordpress.org/plugins/inline-comments/). The first iteration on the CUNY Academic Commons will use Inline Comments, so integration there might be the smoothest.

### Optional integrations

When installed alongside BuddyPress, Social Paper has some additional functionality:

* Papers can be linked to groups (requires bp-groups)
* Creating and editing papers creates items in the activity stream (requires bp-activity)

