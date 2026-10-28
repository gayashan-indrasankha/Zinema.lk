FROM python@sha256:b658b05ce9c4eb97ec6fc19424818a1f31993659c2b505a532f470e83807815d
COPY dev /opt/zinema/dev
WORKDIR /opt/zinema
CMD ["python", "-B", "dev/fakes.py"]
