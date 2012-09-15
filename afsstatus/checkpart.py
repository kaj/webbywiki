#! /usr/bin/python
from subprocess import Popen, PIPE
from re import compile as regex
from common import size_fmt
from datetime import datetime

cell = 'stacken.kth.se'

def readservers(filename='servers.txt'):
    with open(filename, 'r+') as f:
        return (s.replace('\n', '') for s in f.readlines())

serverlist = readservers()

def tr(cell, *data):
    return '<tr>' + \
        ''.join('<%s>%s</%s>' % (cell, d, cell) for d in data) + \
        '</tr>'

print '<table class="listing fsstatus">'
print '  <caption>Volume usage for {}, generated {:%Y-%m-%d %H:%M}.</caption>' \
    .format(cell, datetime.now())
print '  <thead>'
print '   ', tr('th', 'Server', 'Part', 'Used', 'Overview', 'Free', 'Total')
print '  </thead>'
print '  <tbody>'

fmt = regex('^Free space on partition \/vicep(?P<part>\w+):? (?P<free>[-\d]+) K blocks out of total (?P<tot>[-\d]+)')

tfree, ttot = 0, 0
for server in serverlist:
    shortname = server.split('.')[0]
    vospart = Popen(['vos', 'partinfo', server, '-c', cell, '-noauth'],
                    stdout=PIPE)

    for line in vospart.stdout:
        match = fmt.match(line)
        if match:
            free = int(match.group('free')) * 1024
            tot = int(match.group('tot')) * 1024
            used = tot - free
            tfree += free
            ttot += tot

            # TODO Make a first pass to find actual largest.
            largest = max(2*1024**4, tot)
            p = float(used)/tot
            w_u = float(used)/largest
            w_f = float(free)/largest
            print '    ' + tr('td', shortname, match.group('part'),
                              size_fmt(used),
                              '<span class="{3} bar" style="width: {1:.1%};">{0:.0%}</span><span class="free bar" style="width:{2:.1%};">&#160;</span>' \
                                  .format(p, w_u, w_f,
                                          'used full' if p > 0.9 else 'used'),
                              size_fmt(free), size_fmt(tot))
        else:
            print 'got light? no match.'
            exit(1)

print '  <tr class="footer">'
print '    <th role="row">Totalt</th>'
print '    <td></td>'
print '    <td>%s</td>' % size_fmt(ttot - tfree)
print '    <td></td>'
print '    <td>%s</td>' % size_fmt(tfree)
print '    <td>%s</td>' % size_fmt(ttot)
print '  </tr>'
print '  </tbody>'
print '</table>'
