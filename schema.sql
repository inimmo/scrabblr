# drop table if exists scores, matches, players;
# delete from scores; delete from matches;

create table players (
  name varchar(256) not null primary key
);

insert into players values ('Han'), ('Iain') on duplicate key update name=name;

create table matches (
  id int primary key auto_increment,
  match_date datetime null,
  first_player varchar(256) not null,
  foreign key fk_first_player (first_player) references players(name) on update cascade on delete restrict
);

create table scores (
  id int primary key auto_increment,
  match_id int not null,
  turn int not null,
  player_name varchar(256) not null,
  score int signed not null default 0,
  bonus int not null default 0,
  foreign key fk_match (match_id) references matches(id) on update restrict on delete restrict ,
  foreign key fk_player (player_name) references players(name) on update cascade on delete restrict
);

create or replace view winners as
  select m.id as match_id,
         winner.player_name as winner,
         winner.score as winner_score,
         loser.player_name as loser,
         loser.score as loser_score
    from matches m
    join (
        select match_id,
               player_name,
               row_number() over (partition by match_id order by sum(score) desc) as rd,
               sum(score) as score
          from scores
      group by match_id, player_name
    ) winner on m.id = winner.match_id and winner.rd = 1
    join (
        select match_id,
               player_name,
               row_number() over (partition by match_id order by sum(score) desc) as rd,
               sum(score) as score
          from scores
      group by match_id, player_name
    ) loser on m.id = loser.match_id and loser.rd = 2
order by m.id;

create or replace view results as
    select m.id as match_id,
           p.name as player_name,
           sum(score) as score
      from (matches m, players p)
      join scores s on p.name = s.player_name and s.match_id = m.id
  group by m.id, p.name;

create or replace view summary as
    select p.name as name,
           sum(case when (p.name = w.winner) then 1 else 0 end) as wins,
           max(r.score) as max_score,
           min(r.score) as min_score,
           avg(r.score) as avg_score,
           (select count(0) from scores s where s.player_name = p.name and s.bonus > 0) as sevens,
           sum(r.score) as total_points
      from players p join results r on p.name = r.player_name
      join winners w on r.match_id = w.match_id
  group by p.name;
