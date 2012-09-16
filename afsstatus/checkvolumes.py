#! /usr/bin/python
from collections import defaultdict
from common import readservers, size_fmt
from datetime import datetime
from re import compile as regex
from subprocess import Popen, PIPE

cell = 'stacken.kth.se'
serverlist = readservers()

print '<table class="listing" id="volstatus">'
print '  <caption>Full volumes in {0}, generated {1:%Y-%m-%d %H:%M}.</caption>' \
    .format(cell, datetime.now())
print '  <thead><tr>'
print '   <th>Name</th><th>Server</th><th>Part</th><th>Used</th><th>Quota</th>'
print '  </tr></thead>'
print '  <tbody>'

head = regex('^(?P<vol>\S+)\s+\d+ (?P<type>\w\w)\s+(?P<size>\d+) K\s+\w+')
location = regex('^\s+(?P<server>\S+)\s+\/vicep(?P<part>\w)')
quota = regex('^\s+MaxQuota\s+(?P<quota>\d+) K')

volumes = defaultdict(lambda : defaultdict(int))

for server in serverlist:
    shortname = server.split('.')[0]
    vospart = Popen(['vos', 'listvol', server, '-c', cell, '-long', '-noauth'],
                    stdout=PIPE)
    name = ''
    for line in vospart.stdout:
        m = head.match(line)
        if m:
            name = m.group('vol')
            volumes[name]['name'] = name
            volumes[name]['server'] = shortname
            volumes[name]['size'] = 1024 * int(m.group('size'))
            volumes[name]['type'] = m.group('type')
        m = location.match(line)
        if m:
            # volumes[name]['server'] = m.group('server')
            volumes[name]['part'] = m.group('part')
        m = quota.match(line)
        if m:
            volumes[name]['quota'] = 1024 * int(m.group('quota'))
    vospart.wait()

for vol in volumes.values():
    if vol['quota'] > 0 and vol['type'] == 'RW' and \
            not (vol['name'].startswith('home.') or
                 vol['name'].startswith('H.')):
        vol['fill'] = float(vol['size']) / vol['quota']
        if vol['fill'] > 0.9:
            print '    <tr>'
            print '      <td>', vol['name'], '</td>'
            print '      <td>', vol['server'], '</td>'
            print '      <td>', vol['part'], '</td>'
            print '      <td>{0:.0%}</td>'.format(vol['fill'])
            print '      <td>', size_fmt(vol['quota']), '</td>'
            print '    </tr>'
print '  </tbody>'
print '</table>'
