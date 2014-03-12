all: partitions.html diskhogs.html fullvolumes.html

clean:
	-rm servers.txt

servers.txt:
	vos listaddr -c stacken.kth.se -noauth |sort -u > $@.tmp
	mv $@.tmp $@

partitions.html: checkpart.py servers.txt
	./checkpart.py > $@.tmp
	chmod a+r $@.tmp
	mv $@.tmp $@

diskhogs.html: checkhogs.py servers.txt
	./checkhogs.py > $@.tmp
	chmod a+r $@.tmp
	mv $@.tmp $@

fullvolumes.html: checkvolumes.py servers.txt
	./checkvolumes.py > $@.tmp
	chmod a+r $@.tmp
	mv $@.tmp $@
