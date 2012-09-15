#! /usr/bin/python
from collections import defaultdict
from common import readservers, size_fmt
from re import compile as regex
from subprocess import Popen, PIPE

cell = 'stacken.kth.se'
serverlist = readservers()

rwvol = regex('(?P<vol>\S+)\s+(?P<id>\d+)\s+RW\s+(?P<size>\d+)\s+K\s+On-line')

volumes = {}
users = set()

for server in serverlist:
    print 
    vosvols = Popen(['vos', 'listvol', server, '-c', cell, '-noauth'],
                    stdout=PIPE)

    for line in vosvols.stdout:
        match = rwvol.match(line)
        if match:
            name = match.group('vol')
            size = int(match.group('size')) * 1024
            volumes[name] = size
            volnameparts = name.split('.')
            if len(volnameparts) == 2 and volnameparts[0] == 'home':
                users.add(volnameparts[1])

    vosvols.wait()

userspace = defaultdict(int)

for vol, size in volumes.items():
    for vp in vol.split('.'):
        if vp in users:
            userspace[vp] += size

print '<ol class="listing spacewasters">'
for user, size in userspace.items():
    print '  <li>{0} {1}</li>'.format(user, size_fmt(size))
print '</ol>'
