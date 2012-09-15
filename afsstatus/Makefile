all: partitions.html

clean:
	-rm servers.txt

servers.txt:
	vos listvldb -c stacken.kth.se -noauth| awk '/^ +server/ {print $$2}'|sort -u > $@.tmp
	mv $@.tmp $@

partitions.html: checkpart.py servers.txt
	./checkpart.py > $@.tmp
	mv $@.tmp $@
