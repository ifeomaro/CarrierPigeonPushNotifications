MOD=mod_push_notifications
ERLC=erlc
INC=-I ${EJABBERD_PATH} -W0

all: $(MOD)

$(MOD): $(MOD).erl
	$(ERLC) $(INC) $(MOD).erl

install: $(MOD)
	sudo cp $(MOD).beam /lib/ejabberd/ebin/

restart: install
	sudo ejabberdctl restart

clean:
	rm -rf *.beam
