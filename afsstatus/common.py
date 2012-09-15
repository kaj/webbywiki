
def size_fmt(num):
    ''' format a size given as number of bytes with nice unit.
    >>> size_fmt(17), size_fmt(1024), size_fmt(461243), size_fmt(2.4e9)
    ('17 bytes', '1 KB', '450 KB', '2.24 GB')
    >>> size_fmt(1024**2*1000)
    '1000 MB'
    >>> size_fmt(2*1024**4), size_fmt(2.1378*1024**4), size_fmt(768*1024**4)
    ('2 TB', '2.14 TB', '768 TB')
    '''
    def s(value, unit):
        if round(value) >= 100:
            return '{:.0f} {}'.format(value, unit)
        else:
            return '{:.3g} {}'.format(value, unit)

    for x in ['bytes','KB','MB','GB']:
        if num < 1024.0 and num > -1024.0:
            return s(num, x)
        num /= 1024.0
    return s(num, 'TB')


if __name__ == "__main__":
    import doctest
    doctest.testmod()
