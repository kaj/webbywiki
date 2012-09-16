
def readservers(filename='servers.txt'):
    with open(filename, 'r+') as f:
        return (s.replace('\n', '') for s in f.readlines())

def size_fmt(num):
    ''' format a size given as number of bytes with nice unit.
    >>> size_fmt(17), size_fmt(1024), size_fmt(461243), size_fmt(2.4e9)
    ('17 bytes', '1 KiB', '450 KiB', '2.24 GiB')
    >>> size_fmt(1024**2*1000)
    '1000 MiB'
    >>> size_fmt(2*1024**4), size_fmt(2.1378*1024**4), size_fmt(768*1024**4)
    ('2 TiB', '2.14 TiB', '768 TiB')
    '''
    def s(value, unit):
        if round(value) >= 100:
            return '{0:.0f} {1}'.format(value, unit)
        else:
            return '{0:.3g} {1}'.format(value, unit)

    for x in ['bytes','KiB','MiB','GiB']:
        if num < 1024.0 and num > -1024.0:
            return s(num, x)
        num /= 1024.0
    return s(num, 'TiB')


if __name__ == "__main__":
    import doctest
    doctest.testmod()
