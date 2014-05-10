-module(mod_push_notifications).
-author("Ifeoma Okereke").

-behaviour(gen_mod).

-include("ejabberd.hrl").
-include("jlib.hrl").

-type host()	:: string().
-type name()	:: string().
-type value()	:: string().
-type opts()	:: [{name(), value()}, ...].

-define(PROCNAME, ?MODULE).

%% ====================================================================
%% API functions
%% ====================================================================
-export([start/2, init/2, stop/1]).
-export([send_push/3, send_push/4]).

-spec start(host(), opts()) -> ok.
start(Host, Opts) ->
    ?INFO_MSG("starting mod_push_notifications", []),
    register(?PROCNAME,spawn(?MODULE, init, [Host, Opts])),
    ok.

init(Host, Opts) ->
    inets:start(),
    ssl:start(),
    ejabberd_hooks:add(user_receive_packet, Host, ?MODULE, send_push, 0),
    ejabberd_hooks:add(offline_message_hook, Host, ?MODULE, send_push, 0),
    ok.

-spec stop(host()) -> ok.
stop(Host) ->
    ?INFO_MSG("stopping mod_push_notifications", []),
    ejabberd_hooks:delete(user_receive_packet, Host, ?MODULE, send_push, 0),
    ejabberd_hooks:delete(offline_message_hook, Host, ?MODULE, send_push, 0),
    ok.

%% ====================================================================
%% Internal functions
%% ====================================================================
send_push(Jid, From, To, Packet) ->
     Body = xml:get_path_s(Packet, [{elem, "body"}, cdata]),
     Url = "http://<hostname>/apns.php?task=",
     Sep = "&", 
     case xml:get_tag_attr_s("type", Packet) of
	 "chat" ->
	    if (Body /= "") ->
		?INFO_MSG("Calling apns.php", []),
		FullUrlMsg = Url ++ "msg" ++ Sep ++ "to="++ To#jid.luser ++ Sep ++
                           "from=" ++ From#jid.luser ++ Sep ++
                           "body=" ++ url_encode(Body),
		 ?INFO_MSG("URL ~p", [FullUrlMsg]),
        	RespMsg = httpc:request(get, {FullUrlMsg, []},[],[]),
		?INFO_MSG("Response ~p", [RespMsg]),
		ok;
	    true ->
		ok
	    end;
         _ ->
	    ok
      end,
ok.

send_push(From, To, Packet) ->
    NilJid = "",
    send_push(NilJid, From, To, Packet),
ok.

%%% url_encode Source https://github.com/klacke/yaws/blob/master/LICENSE

url_encode([H|T]) when is_list(H) ->
    [url_encode(H) | url_encode(T)];

url_encode([H|T]) ->
    if
        H >= $a, $z >= H ->
            [H|url_encode(T)];
        H >= $A, $Z >= H ->
            [H|url_encode(T)];
        H >= $0, $9 >= H ->
            [H|url_encode(T)];
        H == $_; H == $.; H == $-; H == $/; H == $: -> % FIXME: more..
            [H|url_encode(T)];
        true ->
            case integer_to_hex(H) of
                [X, Y] ->
                    [$%, X, Y | url_encode(T)];
                [X] ->
                    [$%, $0, X | url_encode(T)]
            end
     end;

url_encode([]) ->
    [].

integer_to_hex(I) ->
    case catch erlang:integer_to_list(I, 16) of
        {'EXIT', _} -> old_integer_to_hex(I);
        Int -> Int
    end.

old_integer_to_hex(I) when I < 10 ->
    integer_to_list(I);

old_integer_to_hex(I) when I < 16 ->
    [I-10+$A];

old_integer_to_hex(I) when I >= 16 ->
    N = trunc(I/16),
    old_integer_to_hex(N) ++ old_integer_to_hex(I rem 16).
