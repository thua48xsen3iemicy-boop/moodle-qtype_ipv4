# Product and scope

Moodle 5.3, course activity «Практикум» (Practicum). Teacher adds the activity,
configures blocks and counts, selects training or assessment, and can set a
per-attempt time limit in either mode. Expired training attempts retain a result
for teacher review without a gradebook entry. No manual bank
population. All 12 IPv4 exercise variants plus 29 ip-command and diagnostic tasks are available as mixed blocks with
independently selected allowed prefixes /1–/30.
Linux commands and paths are available as text-response scenarios: 10 file/directory
operations with automatically randomised absolute/relative operands, favouring relative
paths. Both mixed directions are supported; the initial structure remains. No shell is executed.
The file/path family is labelled “File and path operations” in the UI. Teachers
can select entire topic categories and combine them with individual task types.
Advanced blocks also accept multiple types with the same count per type; extra
blocks allow other counts and prefixes.
An explicit “one question per type” mode includes every selected type once,
without entering counts; category labels show how many types are registered.
New network-address questions require the network address with its prefix,
e.g. 192.168.1.0/24. Saved earlier attempts retain their original format.
A separate Linux permissions category adds 17 types: ownership/group commands,
chmod octal/symbolic modes, special bits, mode conversion and permission meaning.
ACLs are excluded for a future separate module. Future families: isolated VM lab tasks.

Confirmed requirements: both training and assessment, mixed task blocks, manual
answers except clickable Yes/No for boolean tasks, independent random prefix selection. Keep the existing IPv4 plugin usable
in normal Moodle quizzes. Do not implement quotas or guaranteed global uniqueness.

The user has another GNS3/xterm.js project; its URL and API are not yet supplied.
Do not invent that integration or claim xterm.js itself executes commands.
