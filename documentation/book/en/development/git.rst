Quick primer on using Git
#########################

These are the Git commands you should be familiar with (in no particular order):

* git clone (-b)
* git submodule (update --init --recursive)
* git remote (-v)
* git branch (-a)
* git remote (-v, set-url)
* git add (-p, -u)
* git rm
* git stash (list, pop)
* git commit (-m)
* git reset (HEAD)

Committing
==========

Before committing, there's staging: Only changes put from your working copy
into the so-called stage are committed when you make a commit.

Staging is done using git add and git rm.

If you use git add, it is highly recommended to use the -p switch. Then
git will show you any single change and ask if you want to stage it or not,
and even gives you a chance to edit a change, for example to split it into
two seperate commits. -p is your friend.

If you delete a lot of files use the -u switch (for update) which automatically
adds all removed files to the stage (pun intended...)

For committing it is recommended to not use the -m switch as you will be presented
with a commit editor which also lists all files to be commited - which is great
to countercheck if any files you didn't intended to be commited are accidentially
staged. (use git reset HEAD <file> to unstage).

For commit messages it is recommended to put a one- or two-word caption at the
start: "WMC Editor: Added more options".

Submodule
=========

Probably the most hard to understand is the use of submodules.

To recapture: Submodules are directories in a Git project which host of
completely different Git project. They do that by storing a pointer to
a specific commit of that submodule in the main project. This is important to
understand: The pointer is not showing to a branch or tag like "master", but
to a specific single commit like "d48832c7117f8aaa4d9fd346d1469c379607813d".

If you update you main project's submodules each submodule is checked out to
that commit and put in a so-called "detached head state", which can be verified
using git status, which will show "Not currently on any branch".

This is easy for submodules used for external projects as we will likely never
touch them anyway.

But for FOM and Mapbender which are also submodules, things tend to create
headaches. We develop in the submodule, commit there and often leave commits
outside any branch, basically making them prone to be lost. Newer git versions
will warn you that your commit is not on a branch, but older ones do not.
Therefore we lay out the following submodule commit workflow.

Workflow
--------

NOTE: This workflow has yet to be tested thoroughly.

NOTE: Mind the branches! As of now the main development is happening on the
following branches. Please take extra care to check that you're committing to
the right branch:

* mapbender-starter: 3.0
* mapbender: 3.0
* fom: master

1) In your main project (mapbender-starter, branch 3.0), do a git pull to get
   all the lastest changes. Then do a git submodule update.
2) Go into the submodule (mapbender or fom) and do your changes. Test them
   locally, but do _not_ commit them. Rather, stash them.
3) As time flies, change back to your main project and do another git pull and
   git submodule update.
4) Go back into your submodule.
5) Checkout the right branch (mapbender: 3.0, fom: master).
6) Unstash your changes. If any conflicts appear, solve them.
7) Commit your changes in the submodule.
8) Push your changes.
9) Go back to your main project.
10) Add and commit the changed submodule path.
11) Push your changes.

This looks like a lot in the beginning, but will get easier over time. And keep
in mind that using submodules makes for better long-time maintainability. And
that you are not alone in scratching your head over the use of submodules. Many
developers do.

